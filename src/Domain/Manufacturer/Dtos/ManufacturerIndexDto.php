<?php

namespace Domain\Manufacturer\Dtos;

use Spatie\LaravelData\Data;
use Spatie\LaravelData\Optional;

class ManufacturerIndexDto extends Data
{
    public function __construct(
        public readonly Optional|string $search,
    ) {}
}
