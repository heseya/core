<?php

declare(strict_types=1);

namespace Domain\ProductAttribute\Dtos;

use Spatie\LaravelData\Data;

final class FiltersDto extends Data
{
    /**
     * @param string[] $sets
     */
    public function __construct(
        public readonly array $sets = [],
    ) {}
}
