<?php

declare(strict_types=1);

namespace Domain\Organization\Dtos;

use Spatie\LaravelData\Data;
use Spatie\LaravelData\Optional;

final class OrganizationIndexDto extends Data
{
    public function __construct(
        public readonly bool|Optional $is_complete,
        public readonly Optional|string $search,
        public readonly Optional|string $sort,
    ) {}
}
