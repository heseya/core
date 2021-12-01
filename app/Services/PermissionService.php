<?php

namespace App\Services;

use App\Models\Permission;
use App\Services\Contracts\PermissionServiceContract;
use Illuminate\Support\Collection;

class PermissionService implements PermissionServiceContract
{
    public function getAll(?bool $assignable): Collection
    {
        if ($assignable !== null) {
            return Permission::search([
                'assignable' => $assignable,
            ])->get();
        }

        return Permission::all();
    }
}
