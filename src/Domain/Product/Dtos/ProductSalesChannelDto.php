<?php

declare(strict_types=1);

namespace Domain\Product\Dtos;

use Domain\SalesChannel\Models\SalesChannel;
use Spatie\LaravelData\Attributes\Validation\Exists;
use Spatie\LaravelData\Data;

final class ProductSalesChannelDto extends Data
{
    public function __construct(
        #[Exists(SalesChannel::class, 'id')]
        public readonly string $id,
        public readonly bool $active,
        public readonly bool $public,
    ) {}
}
