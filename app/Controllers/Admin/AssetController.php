<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Libraries\AuditLogger;
use App\Libraries\UploadSecurity;
use App\Models\AssetModel;
use App\Models\CategoryModel;
use App\Models\PairModel;
use RuntimeException;

class AssetController extends BaseController
{
    public function index(): string
    {
        $assetModel = new AssetModel();

        return view('admin/assets/index', [
            'pageTitle' => 'Assets',
            'assets'    => $assetModel->findAllWithCategoryAndUsage(),
        ]);
    }

    public function create(): string
    {
        $categoryModel = new CategoryModel();

        return view('admin/assets/form', [
            'pageTitle'   => 'Tambah Asset',
            'asset'       => null,
            'categories'  => $categoryModel->orderBy('sort_order', 'ASC')->findAll(),
            'action'      => site_url('/admin/assets'),
        ]);
    }

    public function store()
    {
        $rules = [
            'category_id' => 'required|integer',
            'code'        => 'required|min_length[2]|max_length[50]|is_unique[assets.code]',
            'name'        => 'required|min_length[2]|max_length[150]',
            'symbol'      => 'permit_empty|max_length[20]',
            'decimals'    => 'required|integer|greater_than_equal_to[0]|less_than_equal_to[18]',
            'status'      => 'required|in_list[active,inactive]',
            'sort_order'  => 'permit_empty|integer',
            'capacity_limit' => 'permit_empty|decimal|greater_than_equal_to[0]',
            'hide_threshold_percent' => 'required|integer|greater_than_equal_to[1]|less_than_equal_to[100]',
            'is_auto_hide_enabled' => 'required|in_list[0,1]',
            'destination_account_type' => 'permit_empty|in_list[bank_account,phone_number,other]',
            'destination_account_number' => 'permit_empty|max_length[120]',
            'destination_account_name' => 'permit_empty|max_length[150]',
            'checkout_instruction' => 'permit_empty|max_length[3000]',
            'metadata_json' => 'permit_empty',
        ];

        if (! $this->validate($rules)) {
            return redirect()->back()->withInput()->with('error', $this->validator->getErrors());
        }
        $destinationError = $this->validateDestinationRequirement();
        if ($destinationError !== null) {
            return redirect()->back()->withInput()->with('error', $destinationError);
        }

        $capacityLimitInput = trim((string) ($this->request->getPost('capacity_limit') ?? ''));
        $metadataJson = trim((string) ($this->request->getPost('metadata_json') ?? ''));
        if ($metadataJson !== '' && json_decode($metadataJson, true) === null) {
            return redirect()->back()->withInput()->with('error', 'Metadata JSON tidak valid.');
        }

        $logoPath = null;
        $file     = $this->request->getFile('logo');
        if ($file !== null && $file->isValid() && $file->getName() !== '') {
            try {
                $uploader = new UploadSecurity();
                $logoPath = $uploader->uploadLogo($file);
            } catch (RuntimeException $exception) {
                return redirect()->back()->withInput()->with('error', $exception->getMessage());
            }
        }

        $data = [
            'category_id'   => (int) $this->request->getPost('category_id'),
            'code'          => strtoupper(trim((string) $this->request->getPost('code'))),
            'name'          => trim((string) $this->request->getPost('name')),
            'symbol'        => trim((string) ($this->request->getPost('symbol') ?? '')),
            'decimals'      => (int) $this->request->getPost('decimals'),
            'status'        => (string) $this->request->getPost('status'),
            'sort_order'    => (int) ($this->request->getPost('sort_order') ?? 0),
            'capacity_limit' => $capacityLimitInput !== '' ? (float) $capacityLimitInput : null,
            'hide_threshold_percent' => (int) ($this->request->getPost('hide_threshold_percent') ?? 80),
            'is_auto_hide_enabled' => (int) ($this->request->getPost('is_auto_hide_enabled') ?? 1),
            'destination_account_type' => trim((string) ($this->request->getPost('destination_account_type') ?? '')) ?: null,
            'destination_account_number' => trim((string) ($this->request->getPost('destination_account_number') ?? '')) ?: null,
            'destination_account_name' => trim((string) ($this->request->getPost('destination_account_name') ?? '')) ?: null,
            'checkout_instruction' => trim((string) ($this->request->getPost('checkout_instruction') ?? '')) ?: null,
            'metadata_json' => $metadataJson !== '' ? $metadataJson : null,
            'logo_path'     => $logoPath,
        ];

        $assetModel = new AssetModel();
        $id         = $assetModel->insert($data, true);
        if (! is_int($id)) {
            return redirect()->back()->withInput()->with('error', 'Gagal menyimpan asset.');
        }

        AuditLogger::log([
            'action'     => 'asset_create',
            'model'      => 'assets',
            'model_id'   => $id,
            'new_values' => $data + [
                'operational_config' => $this->extractOperationalConfig($data),
            ],
        ]);

        return redirect()->to('/admin/assets')->with('success', 'Asset berhasil dibuat.');
    }

    public function edit(int $id)
    {
        $assetModel    = new AssetModel();
        $categoryModel = new CategoryModel();
        $asset         = $assetModel->find($id);

        if (! is_array($asset)) {
            return redirect()->to('/admin/assets')->with('error', 'Asset tidak ditemukan.');
        }

        return view('admin/assets/form', [
            'pageTitle'  => 'Edit Asset',
            'asset'      => $asset,
            'categories' => $categoryModel->orderBy('sort_order', 'ASC')->findAll(),
            'action'     => site_url('/admin/assets/' . $id),
        ]);
    }

    public function update(int $id)
    {
        $assetModel = new AssetModel();
        $asset      = $assetModel->find($id);
        if (! is_array($asset)) {
            return redirect()->to('/admin/assets')->with('error', 'Asset tidak ditemukan.');
        }

        $rules = [
            'category_id' => 'required|integer',
            'code'        => 'required|min_length[2]|max_length[50]',
            'name'        => 'required|min_length[2]|max_length[150]',
            'symbol'      => 'permit_empty|max_length[20]',
            'decimals'    => 'required|integer|greater_than_equal_to[0]|less_than_equal_to[18]',
            'status'      => 'required|in_list[active,inactive]',
            'sort_order'  => 'permit_empty|integer',
            'capacity_limit' => 'permit_empty|decimal|greater_than_equal_to[0]',
            'hide_threshold_percent' => 'required|integer|greater_than_equal_to[1]|less_than_equal_to[100]',
            'is_auto_hide_enabled' => 'required|in_list[0,1]',
            'destination_account_type' => 'permit_empty|in_list[bank_account,phone_number,other]',
            'destination_account_number' => 'permit_empty|max_length[120]',
            'destination_account_name' => 'permit_empty|max_length[150]',
            'checkout_instruction' => 'permit_empty|max_length[3000]',
            'metadata_json' => 'permit_empty',
        ];

        if (! $this->validate($rules)) {
            return redirect()->back()->withInput()->with('error', $this->validator->getErrors());
        }
        $destinationError = $this->validateDestinationRequirement();
        if ($destinationError !== null) {
            return redirect()->back()->withInput()->with('error', $destinationError);
        }

        $newCode  = strtoupper(trim((string) $this->request->getPost('code')));
        $codeUsed = $assetModel->where('code', $newCode)->where('id !=', $id)->countAllResults();
        if ($codeUsed > 0) {
            return redirect()->back()->withInput()->with('error', 'Kode asset sudah digunakan.');
        }

        $capacityLimitInput = trim((string) ($this->request->getPost('capacity_limit') ?? ''));
        $metadataJson = trim((string) ($this->request->getPost('metadata_json') ?? ''));
        if ($metadataJson !== '' && json_decode($metadataJson, true) === null) {
            return redirect()->back()->withInput()->with('error', 'Metadata JSON tidak valid.');
        }

        $logoPath = $asset['logo_path'] ?? null;
        $file     = $this->request->getFile('logo');
        if ($file !== null && $file->isValid() && $file->getName() !== '') {
            try {
                $uploader = new UploadSecurity();
                $logoPath = $uploader->uploadLogo($file);
                $this->deleteLogoFile($asset['logo_path'] ?? null);
            } catch (RuntimeException $exception) {
                return redirect()->back()->withInput()->with('error', $exception->getMessage());
            }
        }

        $data = [
            'category_id'   => (int) $this->request->getPost('category_id'),
            'code'          => $newCode,
            'name'          => trim((string) $this->request->getPost('name')),
            'symbol'        => trim((string) ($this->request->getPost('symbol') ?? '')),
            'decimals'      => (int) $this->request->getPost('decimals'),
            'status'        => (string) $this->request->getPost('status'),
            'sort_order'    => (int) ($this->request->getPost('sort_order') ?? 0),
            'capacity_limit' => $capacityLimitInput !== '' ? (float) $capacityLimitInput : null,
            'hide_threshold_percent' => (int) ($this->request->getPost('hide_threshold_percent') ?? 80),
            'is_auto_hide_enabled' => (int) ($this->request->getPost('is_auto_hide_enabled') ?? 1),
            'destination_account_type' => trim((string) ($this->request->getPost('destination_account_type') ?? '')) ?: null,
            'destination_account_number' => trim((string) ($this->request->getPost('destination_account_number') ?? '')) ?: null,
            'destination_account_name' => trim((string) ($this->request->getPost('destination_account_name') ?? '')) ?: null,
            'checkout_instruction' => trim((string) ($this->request->getPost('checkout_instruction') ?? '')) ?: null,
            'metadata_json' => $metadataJson !== '' ? $metadataJson : null,
            'logo_path'     => $logoPath,
        ];

        $assetModel->update($id, $data);

        AuditLogger::log([
            'action'     => 'asset_update',
            'model'      => 'assets',
            'model_id'   => $id,
            'old_values' => $asset,
            'new_values' => $data + [
                'operational_changes' => $this->extractOperationalChanges($asset, $data),
            ],
        ]);

        return redirect()->to('/admin/assets')->with('success', 'Asset berhasil diperbarui.');
    }

    public function delete(int $id)
    {
        $assetModel = new AssetModel();
        $asset      = $assetModel->find($id);
        if (! is_array($asset)) {
            return redirect()->to('/admin/assets')->with('error', 'Asset tidak ditemukan.');
        }

        $pairUsage = (new PairModel())
            ->groupStart()
            ->where('from_asset_id', $id)
            ->orWhere('to_asset_id', $id)
            ->groupEnd()
            ->countAllResults();
        if ($pairUsage > 0) {
            return redirect()->to('/admin/assets')->with('error', 'Asset dipakai di pair. Nonaktifkan dulu, tidak bisa dihapus langsung.');
        }

        $assetModel->delete($id);
        $this->deleteLogoFile($asset['logo_path'] ?? null);

        AuditLogger::log([
            'action'     => 'asset_delete',
            'model'      => 'assets',
            'model_id'   => $id,
            'old_values' => $asset,
        ]);

        return redirect()->to('/admin/assets')->with('success', 'Asset berhasil dihapus.');
    }

    private function deleteLogoFile(?string $path): void
    {
        if ($path === null || $path === '') {
            return;
        }

        $fullPath = ROOTPATH . 'public/' . ltrim($path, '/');
        if (is_file($fullPath)) {
            @unlink($fullPath);
        }
    }

    private function validateDestinationRequirement(): ?string
    {
        $type = trim((string) ($this->request->getPost('destination_account_type') ?? ''));
        $number = trim((string) ($this->request->getPost('destination_account_number') ?? ''));
        if (in_array($type, ['phone_number', 'bank_account'], true) && $number === '') {
            return 'Nomor tujuan wajib diisi untuk tipe rekening atau nomor HP.';
        }

        return null;
    }

    private function extractOperationalChanges(array $old, array $new): array
    {
        $fields = [
            'capacity_limit',
            'hide_threshold_percent',
            'is_auto_hide_enabled',
            'destination_account_type',
            'destination_account_number',
            'destination_account_name',
            'checkout_instruction',
        ];
        $changes = [];
        foreach ($fields as $field) {
            $oldValue = $old[$field] ?? null;
            $newValue = $new[$field] ?? null;
            if ((string) $oldValue !== (string) $newValue) {
                $changes[$field] = [
                    'from' => $oldValue,
                    'to' => $newValue,
                ];
            }
        }

        return $changes;
    }

    private function extractOperationalConfig(array $data): array
    {
        return [
            'capacity_limit' => $data['capacity_limit'] ?? null,
            'hide_threshold_percent' => $data['hide_threshold_percent'] ?? null,
            'is_auto_hide_enabled' => $data['is_auto_hide_enabled'] ?? null,
            'destination_account_type' => $data['destination_account_type'] ?? null,
            'destination_account_number' => $data['destination_account_number'] ?? null,
            'destination_account_name' => $data['destination_account_name'] ?? null,
            'checkout_instruction' => $data['checkout_instruction'] ?? null,
        ];
    }
}
