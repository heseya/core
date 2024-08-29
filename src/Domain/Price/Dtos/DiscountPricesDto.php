<?php

declare(strict_types=1);

namespace Domain\Price\Dtos;

use Domain\Price\Enums\DiscountConditionPriceType;
use Spatie\LaravelData\Attributes\DataCollectionOf;
use Spatie\LaravelData\Attributes\WithCast;
use Spatie\LaravelData\Casts\EnumCast;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\DataCollection;

final class DiscountPricesDto extends Data
{
    protected static string $_collectionClass = DiscountPricesDtoCollection::class;

    /**
     * @param DataCollection<int,PriceDto> $prices
     */
    public function __construct(
        #[WithCast(EnumCast::class, DiscountConditionPriceType::class)]
        public DiscountConditionPriceType $type,
        #[DataCollectionOf(PriceDto::class)]
        public DataCollection $prices,
    ) {}
}
