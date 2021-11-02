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

    public function uninstall(App $app, bool $force = false): void;

    public function appPermissionPrefix(App $app): string;
}
