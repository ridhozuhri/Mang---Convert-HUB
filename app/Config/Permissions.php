<?php

namespace Config;

use CodeIgniter\Config\BaseConfig;

class Permissions extends BaseConfig
{
    public array $matrix = [
        'categories'          => 'admin',
        'assets'              => 'admin',
        'pairs'               => 'admin',
        'payment_methods'     => 'admin',
        'settings'            => 'admin',
        'notifications_blast' => 'admin',
        'users_manage'        => 'admin',
        'rates_update'        => 'staff',
        'orders_manage'       => 'staff',
        'orders_export'       => 'staff',
        'rates_view'          => 'viewer',
        'orders_view'         => 'viewer',
        'audit_view'          => 'viewer',
        'dashboard'           => 'viewer',
    ];

    public array $roleHierarchy = [
        'admin'  => 4,
        'staff'  => 3,
        'viewer' => 2,
        'user'   => 1,
        'public' => 0,
    ];

    public function can(string $userRole, string $resource): bool
    {
        $minimumRole = $this->matrix[$resource] ?? 'admin';
        $userLevel   = $this->roleHierarchy[$userRole] ?? 0;
        $minLevel    = $this->roleHierarchy[$minimumRole] ?? 99;

        return $userLevel >= $minLevel;
    }

    public function atLeast(string $userRole, string $minimumRole): bool
    {
        $userLevel = $this->roleHierarchy[$userRole] ?? 0;
        $minLevel  = $this->roleHierarchy[$minimumRole] ?? 99;

        return $userLevel >= $minLevel;
    }
}
