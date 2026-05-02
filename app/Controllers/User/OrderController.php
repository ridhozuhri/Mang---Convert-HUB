<?php

namespace App\Controllers\User;

use App\Controllers\BaseController;
use App\Libraries\AuditLogger;
use App\Libraries\NotificationService;
use App\Libraries\RateCalculator;
use App\Libraries\ServiceInstructionResolver;
use App\Libraries\UploadSecurity;
use App\Models\AssetModel;
use App\Models\OrderModel;
use App\Models\PairModel;
use App\Models\PairPaymentMethodModel;
use App\Models\PaymentMethodModel;
use RuntimeException;

class OrderController extends BaseController
{
    public function index(): string
    {
        $userId = (int) session()->get('user_id');
        $rows = (new OrderModel())
            ->where('user_id', $userId)
            ->orderBy('id', 'DESC')
            ->findAll();

        return view('user/orders/index', [
            'pageTitle' => 'Order Saya',
            'rows'      => $rows,
        ]);
    }

    public function create()
    {
        $rules = [
            'pair_id'          => 'required|integer',
            'amount_sent'      => 'required|decimal|greater_than[0]',
            'payment_method_id'=> 'permit_empty|integer',
            'customer_sender_account' => 'permit_empty|max_length[100]',
            'idempotency_key' => 'permit_empty|max_length[80]',
        ];
        if (! $this->validate($rules)) {
            return redirect()->back()->withInput()->with('error', $this->validator->getErrors());
        }

        $pairId     = (int) $this->request->getPost('pair_id');
        $amountSent = (float) $this->request->getPost('amount_sent');
        $pair       = (new PairModel())->findWithAssets($pairId);
        if (! is_array($pair) || ($pair['status'] ?? '') !== 'active') {
            return redirect()->back()->withInput()->with('error', 'Pair tidak tersedia.');
        }
        $blockedAssetIds = (new AssetModel())->unavailableAssetIdsByLimit();
        if (in_array((int) ($pair['to_asset_id'] ?? 0), $blockedAssetIds, true)) {
            return redirect()->back()->withInput()->with('error', 'Asset tujuan sedang mencapai batas stok. Pilih asset tujuan lain.');
        }

        $calc = RateCalculator::calculate($amountSent, $pair);
        if (($calc['error'] ?? null) !== null) {
            return redirect()->back()->withInput()->with('error', (string) $calc['error']);
        }

        $paymentMethodId = (int) ($this->request->getPost('payment_method_id') ?? 0);
        if ($paymentMethodId <= 0) {
            $allowedMethodIds = (new PairPaymentMethodModel())->activeMethodIdsByPair($pairId);
            if ($allowedMethodIds !== []) {
                $paymentMethodId = (int) $allowedMethodIds[0];
            } else {
                $pm = (new PaymentMethodModel())->where('status', 'active')->first();
                $paymentMethodId = is_array($pm) ? (int) $pm['id'] : 0;
            }
        }

        $pairMethodRule = null;
        if ($paymentMethodId > 0) {
            $pairMethodRule = (new PairPaymentMethodModel())->findActiveRule($pairId, $paymentMethodId);
        }
        $serviceRules = [];
        if (is_array($pairMethodRule)) {
            $decoded = json_decode((string) ($pairMethodRule['rules_json'] ?? ''), true);
            $serviceRules = is_array($decoded) ? $decoded : [];
        }
        $serviceRules = ServiceInstructionResolver::resolve($pair, $serviceRules);

        $limitMin = isset($serviceRules['tx_min']) && is_numeric($serviceRules['tx_min']) ? (float) $serviceRules['tx_min'] : null;
        $limitMax = isset($serviceRules['tx_max']) && is_numeric($serviceRules['tx_max']) ? (float) $serviceRules['tx_max'] : null;
        if ($limitMin !== null && $amountSent < $limitMin) {
            return redirect()->back()->withInput()->with('error', 'Limit minimum service: ' . $limitMin);
        }
        if ($limitMax !== null && $amountSent > $limitMax) {
            return redirect()->back()->withInput()->with('error', 'Limit maksimum service: ' . $limitMax);
        }

        $customerSenderAccount = trim((string) ($this->request->getPost('customer_sender_account') ?? ''));
        $requireSender = (bool) ($serviceRules['require_sender_account'] ?? false);
        if ($requireSender && $customerSenderAccount === '') {
            return redirect()->back()->withInput()->with('error', 'Nomor/akun pengirim wajib diisi untuk service ini.');
        }

        $orderModel = new OrderModel();
        $code       = $this->generateUniqueOrderCode($orderModel);
        $userId     = (int) session()->get('user_id');
        $idempotencyKey = trim((string) ($this->request->getPost('idempotency_key') ?? ''));
        if ($idempotencyKey === '') {
            $idempotencyKey = sha1($userId . '|' . $pairId . '|' . $amountSent . '|' . date('YmdHi'));
        }
        $existing = $orderModel
            ->where('user_id', $userId)
            ->where('idempotency_key', $idempotencyKey)
            ->first();
        if (is_array($existing)) {
            return redirect()->to('/user/orders/' . $existing['code'])->with('success', 'Order sudah dibuat sebelumnya.');
        }

        $orderId = $orderModel->insert([
            'code'              => $code,
            'user_id'           => $userId,
            'pair_id'           => $pairId,
            'payment_method_id' => $paymentMethodId > 0 ? $paymentMethodId : null,
            'idempotency_key'   => $idempotencyKey,
            'service_rules_snapshot' => $serviceRules !== [] ? json_encode($serviceRules) : null,
            'customer_sender_account' => $customerSenderAccount !== '' ? $customerSenderAccount : null,
            'snap_rate'         => RateCalculator::resolveRate($pair),
            'snap_fee_type'     => $pair['fee_type'],
            'snap_fee_value'    => $pair['fee_value'],
            'snap_spread_type'  => $pair['spread_type'],
            'snap_spread_value' => $pair['spread_value'],
            'snap_min_amount'   => $pair['min_amount'],
            'snap_max_amount'   => $pair['max_amount'],
            'amount_sent'       => $amountSent,
            'amount_received'   => $calc['net'],
            'status'            => 'pending',
            'proof_status'      => 'none',
            'expires_at'        => date('Y-m-d H:i:s', strtotime('+2 hours')),
        ], true);

        if (! is_int($orderId)) {
            return redirect()->back()->withInput()->with('error', 'Gagal membuat order.');
        }

        AuditLogger::log([
            'action'     => 'user_order_create',
            'model'      => 'orders',
            'model_id'   => $orderId,
            'new_values' => ['code' => $code, 'amount_sent' => $amountSent, 'amount_received' => $calc['net']],
        ]);

        NotificationService::sendToRoles(
            ['admin', 'staff'],
            'order_status',
            'Order baru: ' . $code,
            'Order baru dibuat oleh user #' . $userId . '.',
            ['order_code' => $code]
        );

        return redirect()->to('/user/orders/' . $code)->with('success', 'Order berhasil dibuat.');
    }

    public function detail(string $code)
    {
        $userId = (int) session()->get('user_id');
        $row    = (new OrderModel())->where('code', $code)->where('user_id', $userId)->first();
        if (! is_array($row)) {
            return redirect()->to('/user/orders')->with('error', 'Order tidak ditemukan.');
        }

        return view('user/orders/detail', [
            'pageTitle' => 'Detail Order',
            'row'       => $row,
        ]);
    }

    public function uploadProof(string $code)
    {
        $userId = (int) session()->get('user_id');
        $orderModel = new OrderModel();
        $row = $orderModel->where('code', $code)->where('user_id', $userId)->first();
        if (! is_array($row)) {
            return redirect()->to('/user/orders')->with('error', 'Order tidak ditemukan.');
        }
        if (! in_array((string) $row['status'], ['pending', 'paid'], true)) {
            return redirect()->back()->with('error', 'Status order tidak menerima upload bukti.');
        }

        $file = $this->request->getFile('proof');
        if ($file === null || ! $file->isValid()) {
            return redirect()->back()->with('error', 'File bukti tidak valid.');
        }

        try {
            $path = (new UploadSecurity())->uploadProof($userId, $file);
        } catch (RuntimeException $exception) {
            return redirect()->back()->with('error', $exception->getMessage());
        }

        $orderModel->update((int) $row['id'], [
            'proof_path'        => $path,
            'proof_uploaded_at' => date('Y-m-d H:i:s'),
            'proof_status'      => 'pending_review',
        ]);

        NotificationService::sendToRoles(
            ['admin', 'staff'],
            'order_status',
            'Bukti bayar diupload',
            'Order ' . $code . ' telah upload bukti bayar.',
            ['order_code' => $code]
        );

        return redirect()->back()->with('success', 'Bukti berhasil diupload.');
    }

    public function cancel(string $code)
    {
        $userId = (int) session()->get('user_id');
        $orderModel = new OrderModel();
        $row = $orderModel->where('code', $code)->where('user_id', $userId)->first();
        if (! is_array($row)) {
            return redirect()->to('/user/orders')->with('error', 'Order tidak ditemukan.');
        }
        if ((string) $row['status'] !== 'pending') {
            return redirect()->back()->with('error', 'Hanya order pending yang dapat dibatalkan.');
        }

        $reason = trim((string) ($this->request->getPost('reason') ?? 'Dibatalkan user'));
        $orderModel->update((int) $row['id'], [
            'status'           => 'cancelled',
            'cancelled_reason' => $reason,
        ]);

        AuditLogger::log([
            'action'     => 'user_order_cancel',
            'model'      => 'orders',
            'model_id'   => (int) $row['id'],
            'old_values' => ['status' => $row['status']],
            'new_values' => ['status' => 'cancelled', 'reason' => $reason],
        ]);

        return redirect()->back()->with('success', 'Order berhasil dibatalkan.');
    }

    private function generateUniqueOrderCode(OrderModel $orderModel): string
    {
        do {
            $code = 'CH-' . date('Ymd') . '-' . strtoupper(bin2hex(random_bytes(3)));
            $exists = $orderModel->where('code', $code)->countAllResults();
        } while ($exists > 0);

        return $code;
    }
}
