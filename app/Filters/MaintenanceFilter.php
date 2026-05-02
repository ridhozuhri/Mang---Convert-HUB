<?php

namespace App\Filters;

use App\Models\SettingModel;
use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

class MaintenanceFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        $uriPath = trim($request->getUri()->getPath(), '/');
        $allowed = ['maintenance', 'login', 'register', 'logout'];

        if (in_array($uriPath, $allowed, true)) {
            return null;
        }

        $role = (string) session()->get('role');
        if (in_array($role, ['admin', 'staff', 'viewer'], true)) {
            return null;
        }

        $settingModel = new SettingModel();
        $isMaintenance = (bool) $settingModel->getValue('system', 'maintenance_mode', false);

        if ($isMaintenance) {
            return redirect()->to('/maintenance');
        }

        return null;
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
    }
}
