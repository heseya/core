<?php

declare(strict_types=1);

namespace Domain\Product\Dtos;

use Domain\Product\Enums\ProductSalesChannelStatus;
use Domain\SalesChannel\Models\SalesChannel;
use Spatie\LaravelData\Attributes\Validation\Exists;
use Spatie\LaravelData\Attributes\WithCast;
use Spatie\LaravelData\Casts\EnumCast;
use Spatie\LaravelData\Data;

final class ProductSalesChannelDto extends Data
{
    public function __construct(
        #[Exists(SalesChannel::class, 'id')]
        public readonly string $id,
        #[WithCast(EnumCast::class)]
        public ProductSalesChannelStatus $availability_status,
    ) {}
}
