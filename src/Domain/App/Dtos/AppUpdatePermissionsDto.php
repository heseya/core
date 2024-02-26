<?php

declare(strict_types=1);

namespace Domain\App\Dtos;

use Spatie\LaravelData\Data;

final class AppUpdatePermissionsDto extends Data
{
    /**
     * @param array<int, string> $allowed_permissions
     * @param array<int, string> $public_app_permissions
     */
    public function __construct(
        public readonly array $allowed_permissions,
        public readonly array $public_app_permissions = [],
    ) {}
}
