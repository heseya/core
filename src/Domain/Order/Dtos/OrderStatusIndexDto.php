<?php

declare(strict_types=1);

namespace Domain\Order\Dtos;

use Spatie\LaravelData\Data;
use Spatie\LaravelData\Optional;

final class OrderStatusIndexDto extends Data
{
    /**
     * @param string[]|Optional $ids
     * @param string[]|Optional $metadata
     * @param string[]|Optional $metadata_private
     */
    public function __construct(
        public readonly array|Optional $ids,
        public readonly array|Optional $metadata,
        public readonly array|Optional $metadata_private,
    ) {}
}
