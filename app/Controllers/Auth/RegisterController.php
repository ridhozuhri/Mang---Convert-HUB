<?php

namespace App\Controllers\Auth;

use App\Controllers\BaseController;
use App\Libraries\AuditLogger;
use App\Models\UserModel;

class RegisterController extends BaseController
{
    public function index()
    {
        if (session()->get('isLoggedIn')) {
            return redirect()->to('/');
        }

        return view('auth/register', [
            'pageTitle' => 'Daftar',
        ]);
    }

    public function process()
    {
        if (session()->get('isLoggedIn')) {
            return redirect()->to('/');
        }

        $rules = [
            'name'              => 'required|min_length[3]|max_length[100]',
            'email'             => 'required|valid_email|max_length[191]|is_unique[users.email]',
            'password'          => 'required|min_length[8]|max_length[255]',
            'password_confirm'  => 'required|matches[password]',
        ];

        if (! $this->validate($rules)) {
            return redirect()->back()->withInput()->with('error', $this->validator->getErrors());
        }

        $userModel = new UserModel();
        $data      = [
            'name'      => trim((string) $this->request->getPost('name')),
            'email'     => strtolower(trim((string) $this->request->getPost('email'))),
            'password'  => password_hash((string) $this->request->getPost('password'), PASSWORD_ARGON2ID),
            'role'      => 'user',
            'is_active' => 1,
        ];

        $userId = $userModel->insert($data, true);
        if (! is_int($userId)) {
            return redirect()->back()->withInput()->with('error', 'Registrasi gagal. Silakan coba lagi.');
        }

        AuditLogger::log([
            'user_id'    => $userId,
            'action'     => 'auth_register',
            'model'      => 'users',
            'model_id'   => $userId,
            'new_values' => [
                'name'  => $data['name'],
                'email' => $data['email'],
                'role'  => $data['role'],
            ],
        ]);

        return redirect()->to('/login')->with('success', 'Registrasi berhasil. Silakan login.');
    }
}
