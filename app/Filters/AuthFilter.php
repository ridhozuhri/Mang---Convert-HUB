<?php

namespace App\Filters;

use App\Libraries\RBACLibrary;
use App\Models\SettingModel;
use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

class AuthFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        if (! session()->get('isLoggedIn')) {
            return redirect()->to('/login')->with('error', 'Silakan login terlebih dahulu.');
        }

        $settingModel = new SettingModel();
        $timeoutMinutes = (int) $settingModel->getValue('security', 'session_timeout', 120);
        $timeoutMinutes = max(5, $timeoutMinutes);

        $now = time();
        $lastActivity = (int) (session()->get('last_activity_ts') ?? 0);
        if ($lastActivity > 0 && ($now - $lastActivity) > ($timeoutMinutes * 60)) {
            session()->destroy();
            return redirect()->to('/login')->with('error', 'Sesi berakhir karena tidak ada aktivitas.');
        }
        session()->set('last_activity_ts', $now);

        if (is_array($arguments) && $arguments !== []) {
            $role = (string) session()->get('role');
            $minimumRole = (string) $arguments[0];
            $rbac = new RBACLibrary();
            if (! $rbac->hasMinimumRole($role, $minimumRole)) {
                return redirect()->to('/')->with('error', 'Akses tidak diizinkan.');
            }
        }

        return null;
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
    }
}
