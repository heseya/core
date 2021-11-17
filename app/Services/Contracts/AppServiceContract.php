<?php

namespace App\Services\Contracts;

use App\Dtos\AppInstallDto;
use App\Models\App;

interface AppServiceContract
{
    public function install(AppInstallDto $dto): App;

    public function uninstall(App $app, bool $force = false): void;

    public function appPermissionPrefix(App $app): string;
}
