<?php

namespace App\Filters;

use App\Libraries\RBACLibrary;
use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

class RBACFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        if (! session()->get('isLoggedIn')) {
            return redirect()->to('/login')->with('error', 'Silakan login terlebih dahulu.');
        }

        if (! is_array($arguments) || $arguments === []) {
            return redirect()->to('/')->with('error', 'Rule akses tidak valid.');
        }

        $requiredRole = $arguments[0];
        $userRole     = (string) session()->get('role');
        $rbac         = new RBACLibrary();

        if (! $rbac->hasMinimumRole($userRole, $requiredRole)) {
            return redirect()->to('/')->with('error', 'Anda tidak memiliki izin untuk fitur ini.');
        }

        return null;
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
    }
}
