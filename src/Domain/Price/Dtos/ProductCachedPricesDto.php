<?php

declare(strict_types=1);

namespace Domain\Price\Dtos;

use Domain\Price\Enums\ProductPriceType;
use Spatie\LaravelData\Attributes\DataCollectionOf;
use Spatie\LaravelData\Attributes\WithCast;
use Spatie\LaravelData\Casts\EnumCast;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\DataCollection;

final class ProductCachedPricesDto extends Data
{
    protected static string $_collectionClass = ProductCachedPricesDtoCollection::class;

    /**
     * @param DataCollection<int,ProductCachedPriceDto> $prices
     */
    public function __construct(
        #[WithCast(EnumCast::class, ProductPriceType::class)]
        public ProductPriceType $type,
        #[DataCollectionOf(ProductCachedPriceDto::class)]
        public DataCollection $prices,
    ) {}
}
