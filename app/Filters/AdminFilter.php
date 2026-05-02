<?php

namespace App\Filters;

use App\Libraries\RBACLibrary;
use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

class AdminFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        if (! session()->get('isLoggedIn')) {
            return redirect()->to('/login')->with('error', 'Silakan login terlebih dahulu.');
        }

        $role = (string) session()->get('role');
        $rbac = new RBACLibrary();

        if (! $rbac->hasMinimumRole($role, 'viewer')) {
            return redirect()->to('/')->with('error', 'Area admin hanya untuk staff internal.');
        }

        if ((int) (session()->get('two_factor_pending') ?? 0) === 1) {
            return redirect()->to('/')->with('error', '2FA admin aktif. Skeleton 2FA belum diverifikasi.');
        }

        return null;
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
    }
}
