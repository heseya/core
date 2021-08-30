<?php

namespace App\Services\Contracts;

use Illuminate\Support\Collection;

interface AuditServiceContract
{
    public function getAuditsForModel(string $class, string $id): Collection;
}
