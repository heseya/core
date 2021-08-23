<?php

namespace App\Services;

use App\Models\Permission;
use App\Services\Contracts\PermissionServiceContract;
use Illuminate\Support\Collection;

class PermissionService implements PermissionServiceContract
{
    public function getAll(?bool $assignable): Collection
    {
        if ($assignable === false) {
            return Permission::whereIn('name', [])->get();
        }

        return Permission::all();
    }
}
