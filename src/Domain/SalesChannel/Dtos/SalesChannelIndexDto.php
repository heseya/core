<?php

declare(strict_types=1);

namespace Domain\SalesChannel\Dtos;

use Spatie\LaravelData\Data;
use Spatie\LaravelData\Optional;

final class SalesChannelIndexDto extends Data
{
    public function __construct(
        public readonly Optional|string $country,
    ) {}
}
