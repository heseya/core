<?php

namespace App\Services;

use App\Services\Contracts\UserManagementServiceContract;
use Illuminate\Database\Eloquent\Collection;

class UserManagementService implements UserManagementServiceContract
{
    public function index(): Collection
    {
        return new Collection();
    }
}
