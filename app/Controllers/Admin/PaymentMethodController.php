<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Libraries\AuditLogger;
use App\Models\PairModel;
use App\Models\PairPaymentMethodModel;
use App\Models\PaymentMethodModel;

class PaymentMethodController extends BaseController
{
    public function index(): string
    {
        $model = new PaymentMethodModel();
        return view('admin/payment_methods/index', [
            'pageTitle' => 'Service Manager',
            'items'     => $model->orderBy('sort_order', 'ASC')->findAll(),
        ]);
    }

    public function create(): string
    {
        return view('admin/payment_methods/form', [
            'pageTitle' => 'Tambah Service',
            'item'      => null,
            'action'    => site_url('/admin/payment-methods'),
        ]);
    }

    public function store()
    {
        $rules = [
            'name'         => 'required|min_length[2]|max_length[150]',
            'type'         => 'required|in_list[manual,gateway]',
            'instructions' => 'permit_empty',
            'status'       => 'required|in_list[active,inactive]',
            'sort_order'   => 'permit_empty|integer',
        ];
        if (! $this->validate($rules)) {
            return redirect()->back()->withInput()->with('error', $this->validator->getErrors());
        }

        $model = new PaymentMethodModel();
        $name  = trim((string) $this->request->getPost('name'));
        $slug  = $this->generateUniqueSlug($name);
        $data  = [
            'name'         => $name,
            'slug'         => $slug,
            'type'         => (string) $this->request->getPost('type'),
            'instructions' => trim((string) ($this->request->getPost('instructions') ?? '')),
            'status'       => (string) $this->request->getPost('status'),
            'sort_order'   => (int) ($this->request->getPost('sort_order') ?? 0),
        ];
        $id = $model->insert($data, true);
        if (! is_int($id)) {
            return redirect()->back()->withInput()->with('error', 'Gagal membuat payment method.');
        }

        AuditLogger::log([
            'action'     => 'payment_method_create',
            'model'      => 'payment_methods',
            'model_id'   => $id,
            'new_values' => $data,
        ]);

        return redirect()->to('/admin/payment-methods')->with('success', 'Service berhasil dibuat.');
    }

    public function edit(int $id)
    {
        $model = new PaymentMethodModel();
        $item  = $model->find($id);
        if (! is_array($item)) {
            return redirect()->to('/admin/payment-methods')->with('error', 'Service tidak ditemukan.');
        }

        $pairs = (new PairModel())->allWithAssets();
        $linkRows = (new PairPaymentMethodModel())->where('payment_method_id', $id)->findAll();
        $activePairIds = [];
        $pairRules = [];
        foreach ($linkRows as $row) {
            if ((int) ($row['is_active'] ?? 0) === 1) {
                $activePairIds[] = (int) $row['pair_id'];
            }
            $pairId = (int) ($row['pair_id'] ?? 0);
            if ($pairId > 0) {
                $decoded = json_decode((string) ($row['rules_json'] ?? ''), true);
                $pairRules[$pairId] = is_array($decoded) ? $decoded : [];
            }
        }

        return view('admin/payment_methods/form', [
            'pageTitle'      => 'Edit Service',
            'item'           => $item,
            'pairs'          => $pairs,
            'activePairIds'  => $activePairIds,
            'pairRules'      => $pairRules,
            'action'         => site_url('/admin/payment-methods/' . $id),
        ]);
    }

    public function update(int $id)
    {
        $model = new PaymentMethodModel();
        $item  = $model->find($id);
        if (! is_array($item)) {
            return redirect()->to('/admin/payment-methods')->with('error', 'Service tidak ditemukan.');
        }

        $rules = [
            'name'         => 'required|min_length[2]|max_length[150]',
            'type'         => 'required|in_list[manual,gateway]',
            'instructions' => 'permit_empty',
            'status'       => 'required|in_list[active,inactive]',
            'sort_order'   => 'permit_empty|integer',
        ];
        if (! $this->validate($rules)) {
            return redirect()->back()->withInput()->with('error', $this->validator->getErrors());
        }

        $name = trim((string) $this->request->getPost('name'));
        $data = [
            'name'         => $name,
            'slug'         => $this->generateUniqueSlug($name, $id),
            'type'         => (string) $this->request->getPost('type'),
            'instructions' => trim((string) ($this->request->getPost('instructions') ?? '')),
            'status'       => (string) $this->request->getPost('status'),
            'sort_order'   => (int) ($this->request->getPost('sort_order') ?? 0),
        ];
        $model->update($id, $data);

        AuditLogger::log([
            'action'     => 'payment_method_update',
            'model'      => 'payment_methods',
            'model_id'   => $id,
            'old_values' => $item,
            'new_values' => $data,
        ]);

        return redirect()->to('/admin/payment-methods')->with('success', 'Service berhasil diupdate.');
    }

    public function delete(int $id)
    {
        $model = new PaymentMethodModel();
        $item  = $model->find($id);
        if (! is_array($item)) {
            return redirect()->to('/admin/payment-methods')->with('error', 'Service tidak ditemukan.');
        }

        $model->delete($id);
        (new PairPaymentMethodModel())->where('payment_method_id', $id)->delete();

        AuditLogger::log([
            'action'     => 'payment_method_delete',
            'model'      => 'payment_methods',
            'model_id'   => $id,
            'old_values' => $item,
        ]);

        return redirect()->to('/admin/payment-methods')->with('success', 'Service berhasil dihapus.');
    }

    public function updatePairs(int $id)
    {
        $model = new PaymentMethodModel();
        $item  = $model->find($id);
        if (! is_array($item)) {
            return redirect()->to('/admin/payment-methods')->with('error', 'Service tidak ditemukan.');
        }

        $pairIds = $this->request->getPost('pair_ids');
        $pairIds = is_array($pairIds) ? array_map('intval', $pairIds) : [];
        $pairRulesInput = $this->request->getPost('pair_rules');
        $pairRulesInput = is_array($pairRulesInput) ? $pairRulesInput : [];

        $linkModel = new PairPaymentMethodModel();
        $existing = $linkModel->where('payment_method_id', $id)->findAll();
        foreach ($existing as $row) {
            $linkModel->delete((int) $row['id']);
        }
        foreach ($pairIds as $pairId) {
            $ruleInput = is_array($pairRulesInput[$pairId] ?? null) ? $pairRulesInput[$pairId] : [];
            $rules = [
                'tx_min' => trim((string) ($ruleInput['tx_min'] ?? '')),
                'tx_max' => trim((string) ($ruleInput['tx_max'] ?? '')),
                'use_asset_destination' => (string) ($ruleInput['use_asset_destination'] ?? '1') !== '0',
                'destination_number' => trim((string) ($ruleInput['destination_number'] ?? '')),
                'destination_name' => trim((string) ($ruleInput['destination_name'] ?? '')),
                'note_to_customer' => trim((string) ($ruleInput['note_to_customer'] ?? '')),
                'require_sender_account' => (string) ($ruleInput['require_sender_account'] ?? '') === '1',
                'sender_label' => trim((string) ($ruleInput['sender_label'] ?? '')),
            ];
            $linkModel->insert([
                'pair_id'           => $pairId,
                'payment_method_id' => $id,
                'rules_json'        => json_encode($rules),
                'is_active'         => 1,
            ]);
        }

        AuditLogger::log([
            'action'     => 'payment_method_update_pairs',
            'model'      => 'payment_methods',
            'model_id'   => $id,
            'new_values' => ['pair_ids' => $pairIds],
        ]);

        return redirect()->to('/admin/payment-methods/' . $id . '/edit')->with('success', 'Pair assignment berhasil diperbarui.');
    }

    private function generateUniqueSlug(string $name, ?int $ignoreId = null): string
    {
        $base = strtolower(trim(preg_replace('/[^a-zA-Z0-9\s-]/', '', $name) ?? ''));
        $base = trim((string) preg_replace('/\s+/', '-', $base), '-');
        if ($base === '') {
            $base = 'payment-method';
        }
        $slug = $base;
        $suffix = 1;
        $model = new PaymentMethodModel();
        while (true) {
            $query = $model->where('slug', $slug);
            if ($ignoreId !== null) {
                $query->where('id !=', $ignoreId);
            }
            if ($query->countAllResults() === 0) {
                return $slug;
            }
            $slug = $base . '-' . $suffix;
            $suffix++;
        }
    }
}
