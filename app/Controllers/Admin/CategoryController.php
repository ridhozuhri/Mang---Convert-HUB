<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Libraries\AuditLogger;
use App\Models\CategoryModel;

class CategoryController extends BaseController
{
    public function index(): string
    {
        $model = new CategoryModel();

        return view('admin/categories/index', [
            'pageTitle'   => 'Kategori',
            'categories'  => $model->orderBy('sort_order', 'ASC')->orderBy('id', 'ASC')->findAll(),
        ]);
    }

    public function create(): string
    {
        return view('admin/categories/form', [
            'pageTitle' => 'Tambah Kategori',
            'category'  => null,
            'action'    => site_url('/admin/categories'),
        ]);
    }

    public function store()
    {
        $rules = [
            'name'       => 'required|min_length[2]|max_length[100]',
            'sort_order' => 'permit_empty|integer',
            'is_active'  => 'permit_empty|in_list[0,1]',
        ];

        if (! $this->validate($rules)) {
            return redirect()->back()->withInput()->with('error', $this->validator->getErrors());
        }

        $model = new CategoryModel();
        $name  = trim((string) $this->request->getPost('name'));
        $slug  = $this->generateUniqueSlug($name);

        $data = [
            'name'       => $name,
            'slug'       => $slug,
            'sort_order' => (int) ($this->request->getPost('sort_order') ?? 0),
            'is_active'  => (int) ($this->request->getPost('is_active') ?? 1),
        ];

        $id = $model->insert($data, true);
        if (! is_int($id)) {
            return redirect()->back()->withInput()->with('error', 'Gagal menyimpan kategori.');
        }

        AuditLogger::log([
            'action'     => 'category_create',
            'model'      => 'categories',
            'model_id'   => $id,
            'new_values' => $data,
        ]);

        return redirect()->to('/admin/categories')->with('success', 'Kategori berhasil dibuat.');
    }

    public function edit(int $id)
    {
        $model    = new CategoryModel();
        $category = $model->find($id);
        if (! is_array($category)) {
            return redirect()->to('/admin/categories')->with('error', 'Kategori tidak ditemukan.');
        }

        return view('admin/categories/form', [
            'pageTitle' => 'Edit Kategori',
            'category'  => $category,
            'action'    => site_url('/admin/categories/' . $id),
        ]);
    }

    public function update(int $id)
    {
        $model    = new CategoryModel();
        $category = $model->find($id);
        if (! is_array($category)) {
            return redirect()->to('/admin/categories')->with('error', 'Kategori tidak ditemukan.');
        }

        $rules = [
            'name'       => 'required|min_length[2]|max_length[100]',
            'sort_order' => 'permit_empty|integer',
            'is_active'  => 'permit_empty|in_list[0,1]',
        ];

        if (! $this->validate($rules)) {
            return redirect()->back()->withInput()->with('error', $this->validator->getErrors());
        }

        $name = trim((string) $this->request->getPost('name'));
        $slug = $this->generateUniqueSlug($name, $id);

        $data = [
            'name'       => $name,
            'slug'       => $slug,
            'sort_order' => (int) ($this->request->getPost('sort_order') ?? 0),
            'is_active'  => (int) ($this->request->getPost('is_active') ?? 1),
        ];

        $model->update($id, $data);

        AuditLogger::log([
            'action'     => 'category_update',
            'model'      => 'categories',
            'model_id'   => $id,
            'old_values' => $category,
            'new_values' => $data,
        ]);

        return redirect()->to('/admin/categories')->with('success', 'Kategori berhasil diperbarui.');
    }

    public function delete(int $id)
    {
        $model    = new CategoryModel();
        $category = $model->find($id);
        if (! is_array($category)) {
            return redirect()->to('/admin/categories')->with('error', 'Kategori tidak ditemukan.');
        }

        $model->delete($id);

        AuditLogger::log([
            'action'     => 'category_delete',
            'model'      => 'categories',
            'model_id'   => $id,
            'old_values' => $category,
        ]);

        return redirect()->to('/admin/categories')->with('success', 'Kategori berhasil dihapus.');
    }

    private function generateUniqueSlug(string $name, ?int $ignoreId = null): string
    {
        $baseSlug = strtolower(trim(preg_replace('/[^a-zA-Z0-9\s-]/', '', $name) ?? ''));
        $baseSlug = preg_replace('/\s+/', '-', $baseSlug) ?? '';
        $baseSlug = trim($baseSlug, '-');
        if ($baseSlug === '') {
            $baseSlug = 'kategori';
        }

        $slug   = $baseSlug;
        $suffix = 1;
        $model  = new CategoryModel();

        while (true) {
            $query = $model->where('slug', $slug);
            if ($ignoreId !== null) {
                $query = $query->where('id !=', $ignoreId);
            }

            $exists = $query->countAllResults();
            if ($exists === 0) {
                return $slug;
            }

            $slug = $baseSlug . '-' . $suffix;
            $suffix++;
        }
    }
}
