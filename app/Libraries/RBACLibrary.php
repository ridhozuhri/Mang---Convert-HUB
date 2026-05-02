<?php

namespace App\Libraries;

use Config\Permissions;

class RBACLibrary
{
    public function can(string $userRole, string $resource): bool
    {
        $permissions = new Permissions();

        return $permissions->can($userRole, $resource);
    }

    public function hasMinimumRole(string $userRole, string $minimumRole): bool
    {
        $permissions = new Permissions();

        return $permissions->atLeast($userRole, $minimumRole);
    }
}
