<?php

namespace App\Services\Contracts;

use App\Models\App;

interface AppServiceContract
{
    public function install(
        string $url,
        array $permissions,
        ?string $name,
        ?string $licenceKey,
    ): App;
}
