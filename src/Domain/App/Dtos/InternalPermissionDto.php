<?php

declare(strict_types=1);

namespace Domain\App\Dtos;

use Spatie\LaravelData\Data;
use Spatie\LaravelData\Optional;

final class InternalPermissionDto extends Data
{
    public function __construct(
        public readonly string $name,
        public readonly Optional|string|null $display_name,
        public readonly Optional|string|null $description,
    ) {}
}
