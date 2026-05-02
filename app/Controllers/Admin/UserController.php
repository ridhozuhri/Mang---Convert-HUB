<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Libraries\AuditLogger;
use App\Models\UserModel;

class UserController extends BaseController
{
    public function index(): string
    {
        $rows = (new UserModel())
            ->orderBy('id', 'DESC')
            ->findAll();

        return view('admin/users/index', [
            'pageTitle' => 'Management User',
            'rows'      => $rows,
        ]);
    }

    public function create(): string
    {
        return view('admin/users/form', [
            'pageTitle' => 'Tambah User',
            'action'    => site_url('/admin/users'),
            'row'       => null,
        ]);
    }

    public function store()
    {
        $rules = [
            'name'     => 'required|min_length[2]|max_length[100]',
            'email'    => 'required|valid_email|max_length[191]|is_unique[users.email]',
            'role'     => 'required|in_list[admin,staff,viewer,user]',
            'password' => 'required|min_length[8]|max_length[255]',
            'is_active'=> 'required|in_list[0,1]',
        ];
        if (! $this->validate($rules)) {
            return redirect()->back()->withInput()->with('error', $this->validator->getErrors());
        }

        $model = new UserModel();
        $id = $model->insert([
            'name'      => trim((string) $this->request->getPost('name')),
            'email'     => strtolower(trim((string) $this->request->getPost('email'))),
            'role'      => (string) $this->request->getPost('role'),
            'password'  => password_hash((string) $this->request->getPost('password'), PASSWORD_ARGON2ID),
            'is_active' => (int) $this->request->getPost('is_active') === 1 ? 1 : 0,
        ], true);

        if (! is_int($id)) {
            return redirect()->back()->withInput()->with('error', 'Gagal menambah user.');
        }

        AuditLogger::log([
            'action'     => 'user_create',
            'model'      => 'users',
            'model_id'   => $id,
            'new_values' => ['email' => (string) $this->request->getPost('email'), 'role' => (string) $this->request->getPost('role')],
        ]);

        return redirect()->to('/admin/users')->with('success', 'User berhasil ditambahkan.');
    }

    public function edit(int $id)
    {
        $row = (new UserModel())->find($id);
        if (! is_array($row)) {
            return redirect()->to('/admin/users')->with('error', 'User tidak ditemukan.');
        }

        return view('admin/users/form', [
            'pageTitle' => 'Edit User',
            'action'    => site_url('/admin/users/' . $id),
            'row'       => $row,
        ]);
    }

    public function update(int $id)
    {
        $model = new UserModel();
        $row   = $model->find($id);
        if (! is_array($row)) {
            return redirect()->to('/admin/users')->with('error', 'User tidak ditemukan.');
        }

        $rules = [
            'name'      => 'required|min_length[2]|max_length[100]',
            'email'     => 'required|valid_email|max_length[191]|is_unique[users.email,id,' . $id . ']',
            'role'      => 'required|in_list[admin,staff,viewer,user]',
            'password'  => 'permit_empty|min_length[8]|max_length[255]',
            'is_active' => 'required|in_list[0,1]',
        ];
        if (! $this->validate($rules)) {
            return redirect()->back()->withInput()->with('error', $this->validator->getErrors());
        }

        $data = [
            'name'      => trim((string) $this->request->getPost('name')),
            'email'     => strtolower(trim((string) $this->request->getPost('email'))),
            'role'      => (string) $this->request->getPost('role'),
            'is_active' => (int) $this->request->getPost('is_active') === 1 ? 1 : 0,
        ];
        $password = (string) ($this->request->getPost('password') ?? '');
        if ($password !== '') {
            $data['password'] = password_hash($password, PASSWORD_ARGON2ID);
        }

        $model->update($id, $data);

        AuditLogger::log([
            'action'     => 'user_update',
            'model'      => 'users',
            'model_id'   => $id,
            'old_values' => ['email' => (string) ($row['email'] ?? ''), 'role' => (string) ($row['role'] ?? '')],
            'new_values' => ['email' => $data['email'], 'role' => $data['role'], 'is_active' => $data['is_active']],
        ]);

        return redirect()->to('/admin/users')->with('success', 'User berhasil diperbarui.');
    }

    public function toggle(int $id)
    {
        $model = new UserModel();
        $row   = $model->find($id);
        if (! is_array($row)) {
            return redirect()->to('/admin/users')->with('error', 'User tidak ditemukan.');
        }

        $newStatus = (int) (($row['is_active'] ?? 0) ? 0 : 1);
        $model->update($id, ['is_active' => $newStatus]);

        AuditLogger::log([
            'action'     => 'user_toggle_active',
            'model'      => 'users',
            'model_id'   => $id,
            'old_values' => ['is_active' => (int) ($row['is_active'] ?? 0)],
            'new_values' => ['is_active' => $newStatus],
        ]);

        return redirect()->back()->with('success', 'Status user diperbarui.');
    }
}
