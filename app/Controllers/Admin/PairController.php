<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Libraries\AuditLogger;
use App\Models\AssetModel;
use App\Models\PairModel;

class PairController extends BaseController
{
    public function index(): string
    {
        $pairModel = new PairModel();
        $rows = $pairModel->allWithAssets();
        $status = trim((string) ($this->request->getGet('status') ?? ''));
        $keyword = trim((string) ($this->request->getGet('q') ?? ''));

        if ($status !== '' && in_array($status, ['active', 'inactive'], true)) {
            $rows = array_values(array_filter($rows, static fn(array $item): bool => (string) ($item['status'] ?? '') === $status));
        }
        if ($keyword !== '') {
            $needle = mb_strtolower($keyword);
            $rows = array_values(array_filter($rows, static function (array $item) use ($needle): bool {
                $fromCode = mb_strtolower((string) ($item['from_code'] ?? ''));
                $toCode = mb_strtolower((string) ($item['to_code'] ?? ''));
                $fromName = mb_strtolower((string) ($item['from_name'] ?? ''));
                $toName = mb_strtolower((string) ($item['to_name'] ?? ''));
                return str_contains($fromCode, $needle) || str_contains($toCode, $needle) || str_contains($fromName, $needle) || str_contains($toName, $needle);
            }));
        }

        return view('admin/pairs/index', [
            'pageTitle' => 'Pairs',
            'pairs'     => $rows,
            'filters'   => [
                'status' => $status,
                'q' => $keyword,
            ],
        ]);
    }

    public function create(): string
    {
        $assetModel = new AssetModel();

        return view('admin/pairs/form', [
            'pageTitle' => 'Tambah Pair',
            'pair'      => null,
            'assets'    => $assetModel->orderBy('code', 'ASC')->findAll(),
            'action'    => site_url('/admin/pairs'),
        ]);
    }

    public function store()
    {
        $rules = $this->rules();
        $rules['to_asset_id'] .= '|differs[from_asset_id]';

        if (! $this->validate($rules)) {
            return redirect()->back()->withInput()->with('error', $this->validator->getErrors());
        }

        $pairModel = new PairModel();
        $fromAssetId = (int) $this->request->getPost('from_asset_id');
        $toAssetId = (int) $this->request->getPost('to_asset_id');

        $exists = $pairModel->where('from_asset_id', $fromAssetId)->where('to_asset_id', $toAssetId)->countAllResults();
        if ($exists > 0) {
            return redirect()->back()->withInput()->with('error', 'Pair dari dan ke asset tersebut sudah ada.');
        }

        $data = $this->payload();
        $id   = $pairModel->insert($data, true);
        if (! is_int($id)) {
            return redirect()->back()->withInput()->with('error', 'Gagal menyimpan pair.');
        }

        AuditLogger::log([
            'action'     => 'pair_create',
            'model'      => 'pairs',
            'model_id'   => $id,
            'new_values' => $data,
        ]);

        return redirect()->to('/admin/pairs')->with('success', 'Pair berhasil dibuat.');
    }

    public function edit(int $id)
    {
        $pairModel  = new PairModel();
        $assetModel = new AssetModel();
        $pair       = $pairModel->find($id);

        if (! is_array($pair)) {
            return redirect()->to('/admin/pairs')->with('error', 'Pair tidak ditemukan.');
        }

        return view('admin/pairs/form', [
            'pageTitle' => 'Edit Pair',
            'pair'      => $pair,
            'assets'    => $assetModel->orderBy('code', 'ASC')->findAll(),
            'action'    => site_url('/admin/pairs/' . $id),
        ]);
    }

    public function update(int $id)
    {
        $pairModel = new PairModel();
        $pair      = $pairModel->find($id);
        if (! is_array($pair)) {
            return redirect()->to('/admin/pairs')->with('error', 'Pair tidak ditemukan.');
        }

        $rules = $this->rules();
        $rules['to_asset_id'] .= '|differs[from_asset_id]';

        if (! $this->validate($rules)) {
            return redirect()->back()->withInput()->with('error', $this->validator->getErrors());
        }

        $fromAssetId = (int) $this->request->getPost('from_asset_id');
        $toAssetId = (int) $this->request->getPost('to_asset_id');
        $exists = $pairModel
            ->where('from_asset_id', $fromAssetId)
            ->where('to_asset_id', $toAssetId)
            ->where('id !=', $id)
            ->countAllResults();
        if ($exists > 0) {
            return redirect()->back()->withInput()->with('error', 'Pair dari dan ke asset tersebut sudah ada.');
        }

        $data = $this->payload();
        $pairModel->update($id, $data);

        AuditLogger::log([
            'action'     => 'pair_update',
            'model'      => 'pairs',
            'model_id'   => $id,
            'old_values' => $pair,
            'new_values' => $data,
        ]);

        return redirect()->to('/admin/pairs')->with('success', 'Pair berhasil diperbarui.');
    }

    public function delete(int $id)
    {
        $pairModel = new PairModel();
        $pair      = $pairModel->find($id);
        if (! is_array($pair)) {
            return redirect()->to('/admin/pairs')->with('error', 'Pair tidak ditemukan.');
        }

        $pairModel->delete($id);

        AuditLogger::log([
            'action'     => 'pair_delete',
            'model'      => 'pairs',
            'model_id'   => $id,
            'old_values' => $pair,
        ]);

        return redirect()->to('/admin/pairs')->with('success', 'Pair berhasil dihapus.');
    }

    private function rules(): array
    {
        return [
            'from_asset_id'      => 'required|integer',
            'to_asset_id'        => 'required|integer',
            'status'             => 'required|in_list[active,inactive]',
            'sort_order'         => 'permit_empty|integer',
            'min_amount'         => 'required|decimal',
            'max_amount'         => 'required|decimal',
            'fee_type'           => 'required|in_list[fixed,percent]',
            'fee_value'          => 'required|decimal',
            'spread_type'        => 'required|in_list[fixed,percent,none]',
            'spread_value'       => 'required|decimal',
            'rounding_mode'      => 'required|in_list[ceil,floor,round]',
            'rounding_precision' => 'required|integer|greater_than_equal_to[0]|less_than_equal_to[18]',
            'rate_mode'          => 'required|in_list[manual,inverse]',
            'last_rate'          => 'required|decimal|greater_than[0]',
            'notes'              => 'permit_empty|max_length[65535]',
        ];
    }

    private function payload(): array
    {
        return [
            'from_asset_id'      => (int) $this->request->getPost('from_asset_id'),
            'to_asset_id'        => (int) $this->request->getPost('to_asset_id'),
            'status'             => (string) $this->request->getPost('status'),
            'sort_order'         => (int) ($this->request->getPost('sort_order') ?? 0),
            'min_amount'         => (string) $this->request->getPost('min_amount'),
            'max_amount'         => (string) $this->request->getPost('max_amount'),
            'fee_type'           => (string) $this->request->getPost('fee_type'),
            'fee_value'          => (string) $this->request->getPost('fee_value'),
            'spread_type'        => (string) $this->request->getPost('spread_type'),
            'spread_value'       => (string) $this->request->getPost('spread_value'),
            'rounding_mode'      => (string) $this->request->getPost('rounding_mode'),
            'rounding_precision' => (int) $this->request->getPost('rounding_precision'),
            'rate_mode'          => (string) $this->request->getPost('rate_mode'),
            'last_rate'          => (string) $this->request->getPost('last_rate'),
            'last_rate_updated_at' => date('Y-m-d H:i:s'),
            'last_rate_updated_by' => (int) (session()->get('user_id') ?? 0),
            'notes'              => trim((string) ($this->request->getPost('notes') ?? '')),
        ];
    }
}
