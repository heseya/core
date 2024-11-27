<?php

declare(strict_types=1);

namespace Domain\Manufacturer\Dtos;

use Spatie\LaravelData\Data;
use Spatie\LaravelData\Optional;

final class ManufacturerIndexDto extends Data
{
    public function __construct(
        public readonly Optional|string $search,
    ) {}
}
