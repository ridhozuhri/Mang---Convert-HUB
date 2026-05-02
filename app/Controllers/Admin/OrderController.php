<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Libraries\AuditLogger;
use App\Libraries\NotificationService;
use App\Models\AssetLedgerEntryModel;
use App\Models\AssetModel;
use App\Models\OrderModel;

class OrderController extends BaseController
{
    private const ORDER_STATUSES = ['pending', 'paid', 'processing', 'completed', 'cancelled', 'failed'];

    public function index(): string
    {
        $this->escalateOverdueSla();
        $filters = $this->filters();
        $model   = new OrderModel();

        return view('admin/orders/index', [
            'pageTitle' => 'Orders',
            'rows'      => $this->applyExtraFilters($model->listWithRelations($filters), $filters),
            'filters'   => $filters,
        ]);
    }

    public function detail(string $code)
    {
        $model = new OrderModel();
        $row   = $model->findByCode($code);
        if (! is_array($row)) {
            return redirect()->to('/admin/orders')->with('error', 'Order tidak ditemukan.');
        }

        return view('admin/orders/detail', [
            'pageTitle' => 'Detail Order',
            'row'       => $row,
        ]);
    }

    public function updateStatus(string $code)
    {
        $model = new OrderModel();
        $row   = $model->findByCode($code);
        if (! is_array($row)) {
            return redirect()->to('/admin/orders')->with('error', 'Order tidak ditemukan.');
        }

        $rules = [
            'status'     => 'required|in_list[pending,paid,processing,completed,cancelled,failed]',
            'admin_note' => 'permit_empty|max_length[65535]',
        ];
        if (! $this->validate($rules)) {
            return redirect()->back()->withInput()->with('error', $this->validator->getErrors());
        }

        $newStatus = (string) $this->request->getPost('status');
        $adminNote = trim((string) ($this->request->getPost('admin_note') ?? ''));
        $lockError = $this->checkLockOwnership($row);
        if ($lockError !== null) {
            return redirect()->back()->with('error', $lockError);
        }
        if (in_array($newStatus, ['cancelled', 'failed'], true) && $adminNote === '') {
            return redirect()->back()->withInput()->with('error', 'Admin note wajib untuk status cancelled/failed.');
        }
        $apply = $this->applyStatusTransition($model, $row, $newStatus, $adminNote);
        if ($apply !== null) {
            return redirect()->back()->withInput()->with('error', $apply);
        }

        $usageInfoMessage = '';
        if (in_array($newStatus, ['completed', 'cancelled'], true)) {
            $toAssetId = (int) ($row['to_asset_id'] ?? 0);
            if ($toAssetId > 0) {
                $summary = (new AssetModel())->usageSummaryByAssetId($toAssetId);
                if (is_array($summary) && $summary['usage_percent'] !== null) {
                    $usageInfoMessage = ' Usage asset tujuan sekarang: ' . number_format((float) $summary['usage_percent'], 2, '.', ',') . '%.';
                }
            }
        }

        return redirect()->back()->with('success', 'Status order diperbarui.' . $usageInfoMessage);
    }

    public function quickAction(string $code)
    {
        $action = (string) ($this->request->getPost('action') ?? '');
        $map = [
            'approve' => 'paid',
            'process' => 'processing',
            'complete' => 'completed',
            'reject' => 'failed',
        ];
        if (! isset($map[$action])) {
            return redirect()->back()->with('error', 'Aksi cepat tidak valid.');
        }
        $model = new OrderModel();
        $row = $model->findByCode($code);
        if (! is_array($row)) {
            return redirect()->back()->with('error', 'Order tidak ditemukan.');
        }
        $adminNote = trim((string) ($this->request->getPost('admin_note') ?? ''));
        if ($action === 'reject' && $adminNote === '') {
            $adminNote = 'Ditolak via quick action';
        }
        $lockError = $this->checkLockOwnership($row);
        if ($lockError !== null) {
            return redirect()->back()->with('error', $lockError);
        }
        $apply = $this->applyStatusTransition($model, $row, $map[$action], $adminNote);
        if ($apply !== null) {
            return redirect()->back()->with('error', $apply);
        }

        return redirect()->back()->with('success', 'Quick action berhasil: ' . $action . '.');
    }

    public function verifyProof(string $code)
    {
        $decision = (string) ($this->request->getPost('decision') ?? '');
        if (! in_array($decision, ['verify', 'reject'], true)) {
            return redirect()->back()->with('error', 'Keputusan verifikasi bukti tidak valid.');
        }
        $model = new OrderModel();
        $row = $model->findByCode($code);
        if (! is_array($row)) {
            return redirect()->back()->with('error', 'Order tidak ditemukan.');
        }
        if (empty($row['proof_path'])) {
            return redirect()->back()->with('error', 'Order belum memiliki bukti pembayaran.');
        }
        $note = trim((string) ($this->request->getPost('admin_note') ?? ''));
        $status = $decision === 'verify' ? 'verified' : 'rejected';
        $update = [
            'proof_status' => $status,
            'proof_reviewed_at' => date('Y-m-d H:i:s'),
            'proof_reviewed_by' => (int) session()->get('user_id'),
        ];
        if ($decision === 'verify' && (string) ($row['status'] ?? '') === 'pending') {
            $update['status'] = 'paid';
            if (empty($row['sla_due_at'])) {
                $update['sla_due_at'] = date('Y-m-d H:i:s', strtotime('+30 minutes'));
            }
        }
        if ($decision === 'reject' && $note !== '') {
            $update['admin_note'] = $note;
        }
        $model->update((int) $row['id'], $update);

        NotificationService::sendToUser(
            (int) $row['user_id'],
            'order_status',
            'Bukti pembayaran ' . ($decision === 'verify' ? 'diverifikasi' : 'ditolak'),
            'Order ' . $row['code'] . ': bukti pembayaran ' . ($decision === 'verify' ? 'valid' : 'ditolak') . '.',
            ['order_code' => $row['code'], 'proof_status' => $status]
        );

        return redirect()->back()->with('success', 'Review bukti berhasil: ' . $status . '.');
    }

    public function bulkAction()
    {
        $codes = $this->request->getPost('codes');
        $codes = is_array($codes) ? array_values(array_filter(array_map(static fn($item) => trim((string) $item), $codes))) : [];
        if ($codes === []) {
            return redirect()->back()->with('error', 'Pilih minimal 1 order.');
        }
        $action = (string) ($this->request->getPost('bulk_action') ?? '');
        $map = [
            'approve' => 'paid',
            'process' => 'processing',
            'complete' => 'completed',
            'reject' => 'failed',
            'lock' => 'lock',
            'unlock' => 'unlock',
        ];
        if (! isset($map[$action])) {
            return redirect()->back()->with('error', 'Bulk action tidak valid.');
        }
        $model = new OrderModel();
        $success = 0;
        $failed = 0;
        $adminNote = trim((string) ($this->request->getPost('admin_note') ?? ''));
        foreach ($codes as $code) {
            $row = $model->findByCode($code);
            if (! is_array($row)) {
                $failed++;
                continue;
            }
            if ($action === 'lock') {
                if ($this->checkLockOwnership($row) === null && $this->acquireLockAtomic((int) $row['id'])) {
                    $success++;
                } else {
                    $failed++;
                }
                continue;
            }
            if ($action === 'unlock') {
                if ($this->checkLockOwnership($row) === null) {
                    $model->update((int) $row['id'], ['locked_by' => null, 'locked_at' => null]);
                    $success++;
                } else {
                    $failed++;
                }
                continue;
            }
            if ($this->checkLockOwnership($row) !== null) {
                $failed++;
                continue;
            }
            $result = $this->applyStatusTransition($model, $row, $map[$action], $adminNote);
            if ($result === null) {
                $success++;
            } else {
                $failed++;
            }
        }

        return redirect()->back()->with('success', 'Bulk selesai. Berhasil: ' . $success . ', gagal: ' . $failed . '.');
    }

    public function lock(string $code)
    {
        $model = new OrderModel();
        $row = $model->findByCode($code);
        if (! is_array($row)) {
            return redirect()->back()->with('error', 'Order tidak ditemukan.');
        }
        if (! empty($row['locked_by']) && (int) $row['locked_by'] !== (int) session()->get('user_id')) {
            return redirect()->back()->with('error', 'Order sedang dikunci admin lain.');
        }
        if (! $this->acquireLockAtomic((int) $row['id'])) {
            return redirect()->back()->with('error', 'Gagal lock order. Mungkin sudah di-lock admin lain.');
        }

        return redirect()->back()->with('success', 'Order berhasil dikunci.');
    }

    public function unlock(string $code)
    {
        $model = new OrderModel();
        $row = $model->findByCode($code);
        if (! is_array($row)) {
            return redirect()->back()->with('error', 'Order tidak ditemukan.');
        }
        $lockError = $this->checkLockOwnership($row);
        if ($lockError !== null) {
            return redirect()->back()->with('error', $lockError);
        }
        $model->update((int) $row['id'], ['locked_by' => null, 'locked_at' => null]);

        return redirect()->back()->with('success', 'Lock order dibuka.');
    }

    public function exportCsv()
    {
        $filters = $this->filters();
        $rows = (new OrderModel())->listWithRelations($filters);

        $fileName = 'orders-' . date('Ymd-His') . '.csv';
        $output = fopen('php://temp', 'w+b');
        if ($output === false) {
            return redirect()->back()->with('error', 'Gagal membuat file CSV.');
        }

        fwrite($output, "\xEF\xBB\xBF");
        fputcsv($output, ['Code', 'User', 'Pair', 'Payment Method', 'Amount Sent', 'Amount Received', 'Status', 'Created At']);
        foreach ($rows as $row) {
            fputcsv($output, [
                $this->safeCsvCell((string) ($row['code'] ?? '-')),
                $this->safeCsvCell(($row['user_name'] ?? '-') . ' <' . ($row['user_email'] ?? '-') . '>'),
                $this->safeCsvCell(($row['from_code'] ?? '-') . '->' . ($row['to_code'] ?? '-')),
                $this->safeCsvCell((string) ($row['payment_method_name'] ?? '-')),
                $this->safeCsvCell((string) ($row['amount_sent'] ?? '0')),
                $this->safeCsvCell((string) ($row['amount_received'] ?? '0')),
                $this->safeCsvCell((string) ($row['status'] ?? '-')),
                $this->safeCsvCell((string) ($row['created_at'] ?? '-')),
            ]);
        }
        rewind($output);
        $csv = stream_get_contents($output);
        fclose($output);
        if ($csv === false) {
            return redirect()->back()->with('error', 'Gagal membaca file CSV.');
        }

        return $this->response
            ->setHeader('Content-Type', 'text/csv; charset=UTF-8')
            ->setHeader('Content-Disposition', 'attachment; filename="' . $fileName . '"')
            ->setBody($csv);
    }

    private function filters(): array
    {
        $status = trim((string) ($this->request->getGet('status') ?? ''));
        if ($status !== '' && ! in_array($status, self::ORDER_STATUSES, true)) {
            $status = '';
        }

        $dateFrom = $this->normalizeDate((string) ($this->request->getGet('date_from') ?? ''));
        $dateTo   = $this->normalizeDate((string) ($this->request->getGet('date_to') ?? ''));

        return [
            'status'     => $status,
            'proof_status' => trim((string) ($this->request->getGet('proof_status') ?? '')),
            'sla_state'  => trim((string) ($this->request->getGet('sla_state') ?? '')),
            'code'       => trim((string) ($this->request->getGet('code') ?? '')),
            'user_email' => trim((string) ($this->request->getGet('user_email') ?? '')),
            'date_from'  => $dateFrom,
            'date_to'    => $dateTo,
        ];
    }

    private function normalizeDate(string $value): string
    {
        $value = trim($value);
        if ($value === '') {
            return '';
        }

        $date = \DateTime::createFromFormat('Y-m-d', $value);
        if ($date === false || $date->format('Y-m-d') !== $value) {
            return '';
        }

        return $value;
    }

    private function safeCsvCell(string $value): string
    {
        $value = str_replace(["\r", "\n"], ' ', $value);
        if ($value !== '' && in_array($value[0], ['=', '+', '-', '@'], true)) {
            return "'" . $value;
        }

        return $value;
    }

    private function applyExtraFilters(array $rows, array $filters): array
    {
        $proofStatus = trim((string) ($filters['proof_status'] ?? ''));
        if ($proofStatus !== '') {
            $rows = array_values(array_filter($rows, static fn(array $row): bool => (string) ($row['proof_status'] ?? 'none') === $proofStatus));
        }
        $slaState = trim((string) ($filters['sla_state'] ?? ''));
        if ($slaState !== '') {
            $now = time();
            $rows = array_values(array_filter($rows, static function (array $row) use ($slaState, $now): bool {
                $sla = trim((string) ($row['sla_due_at'] ?? ''));
                if ($sla === '') {
                    return $slaState === 'none';
                }
                $remaining = strtotime($sla) - $now;
                return match ($slaState) {
                    'ontrack' => $remaining > 300,
                    'warning' => $remaining <= 300 && $remaining >= 0,
                    'overdue' => $remaining < 0,
                    default => true,
                };
            }));
        }

        return $rows;
    }

    private function checkLockOwnership(array $row): ?string
    {
        $lockedBy = (int) ($row['locked_by'] ?? 0);
        $currentUserId = (int) session()->get('user_id');
        if ($lockedBy > 0 && $lockedBy !== $currentUserId) {
            return 'Order sedang dikunci oleh admin lain: ' . (string) ($row['locked_by_name'] ?? 'Unknown') . '.';
        }

        return null;
    }

    private function applyStatusTransition(OrderModel $model, array $row, string $newStatus, string $adminNote = ''): ?string
    {
        if (in_array($newStatus, ['cancelled', 'failed'], true) && $adminNote === '') {
            return 'Admin note wajib untuk status cancelled/failed.';
        }
        $allowed = [
            'pending'    => ['paid', 'cancelled'],
            'paid'       => ['processing', 'failed'],
            'processing' => ['completed', 'failed'],
            'completed'  => [],
            'cancelled'  => [],
            'failed'     => [],
        ];
        $current = (string) ($row['status'] ?? 'pending');
        if (! in_array($newStatus, $allowed[$current] ?? [], true) && $newStatus !== $current) {
            return 'Perubahan status tidak valid dari status saat ini.';
        }
        $proofStatus = (string) ($row['proof_status'] ?? 'none');
        $proofPath = trim((string) ($row['proof_path'] ?? ''));
        if (in_array($newStatus, ['paid', 'processing', 'completed'], true) && $proofPath !== '' && $proofStatus !== 'verified') {
            return 'Bukti pembayaran belum diverifikasi. Verifikasi proof terlebih dahulu.';
        }

        $updateData = [
            'status'    => $newStatus,
            'admin_note'=> $adminNote !== '' ? $adminNote : null,
        ];
        if ($newStatus === 'completed') {
            $updateData['completed_at'] = date('Y-m-d H:i:s');
            $updateData['locked_by'] = null;
            $updateData['locked_at'] = null;
        }
        if ($newStatus === 'cancelled') {
            $updateData['cancelled_reason'] = $adminNote;
            $updateData['locked_by'] = null;
            $updateData['locked_at'] = null;
        }
        if ($newStatus === 'failed') {
            $updateData['locked_by'] = null;
            $updateData['locked_at'] = null;
        }
        if (in_array($newStatus, ['paid', 'processing'], true) && empty($row['sla_due_at'])) {
            $updateData['sla_due_at'] = date('Y-m-d H:i:s', strtotime('+30 minutes'));
        }
        if ($newStatus === 'processing' && empty($row['locked_by'])) {
            $updateData['locked_by'] = (int) session()->get('user_id');
            $updateData['locked_at'] = date('Y-m-d H:i:s');
        }

        $model->update((int) $row['id'], $updateData);

        if ($newStatus === 'completed') {
            $this->recordLedgerForCompleted($row);
        }

        AuditLogger::log([
            'action'     => 'order_status_update',
            'model'      => 'orders',
            'model_id'   => (int) $row['id'],
            'old_values' => ['status' => $current],
            'new_values' => ['status' => $newStatus, 'admin_note' => $adminNote],
        ]);
        NotificationService::sendToUser(
            (int) $row['user_id'],
            'order_status',
            'Status order diperbarui',
            'Order ' . $row['code'] . ' sekarang: ' . strtoupper($newStatus),
            ['order_code' => $row['code'], 'status' => $newStatus]
        );

        return null;
    }

    private function acquireLockAtomic(int $orderId): bool
    {
        $builder = (new OrderModel())->builder();
        $builder->set('locked_by', (int) session()->get('user_id'));
        $builder->set('locked_at', date('Y-m-d H:i:s'));
        $builder->where('id', $orderId);
        $builder->groupStart()->where('locked_by', null)->orWhere('locked_by', 0)->orWhere('locked_by', (int) session()->get('user_id'))->groupEnd();
        $builder->update();

        return $builder->db()->affectedRows() > 0;
    }

    private function escalateOverdueSla(): void
    {
        $rows = (new OrderModel())
            ->whereIn('status', ['paid', 'processing'])
            ->where('sla_due_at <', date('Y-m-d H:i:s'))
            ->where('sla_escalated_at', null)
            ->findAll(50);
        if ($rows === []) {
            return;
        }
        $model = new OrderModel();
        foreach ($rows as $row) {
            NotificationService::sendToRoles(
                ['admin', 'staff'],
                'order_status',
                'SLA Overdue: ' . $row['code'],
                'Order melewati SLA, segera tindak lanjut.',
                ['order_code' => $row['code']]
            );
            $model->update((int) $row['id'], ['sla_escalated_at' => date('Y-m-d H:i:s')]);
        }
    }

    private function recordLedgerForCompleted(array $row): void
    {
        $orderId = (int) ($row['id'] ?? 0);
        $assetId = (int) ($row['to_asset_id'] ?? 0);
        if ($orderId <= 0 || $assetId <= 0) {
            return;
        }
        $ledger = new AssetLedgerEntryModel();
        $exists = $ledger->where('order_id', $orderId)->where('entry_type', 'debit')->countAllResults();
        if ($exists > 0) {
            return;
        }
        $ledger->insert([
            'asset_id' => $assetId,
            'order_id' => $orderId,
            'entry_type' => 'debit',
            'amount' => (string) ($row['amount_received'] ?? '0'),
            'notes' => 'Order completed: ' . (string) ($row['code'] ?? ''),
            'created_by' => (int) session()->get('user_id'),
            'created_at' => date('Y-m-d H:i:s'),
        ]);
    }
}
