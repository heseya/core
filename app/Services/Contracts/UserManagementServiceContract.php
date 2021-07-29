<?php

namespace App\Services\Contracts;

use Illuminate\Database\Eloquent\Collection;

interface UserManagementServiceContract
{
    public function index(): Collection;
}
