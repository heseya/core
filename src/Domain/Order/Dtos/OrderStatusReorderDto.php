<?php

declare(strict_types=1);

namespace Domain\Order\Dtos;

use Spatie\LaravelData\Data;

final class OrderStatusReorderDto extends Data
{
    /**
     * @param string[] $statuses
     */
    public function __construct(
        public readonly array $statuses,
    ) {}
}
