<?php

namespace App\Services\Contracts;

use Illuminate\Support\Collection;

interface PermissionServiceContract
{
    public function getAll(bool $assignable): Collection;
}
