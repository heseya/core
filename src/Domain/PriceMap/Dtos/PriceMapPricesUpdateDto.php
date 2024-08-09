<?php

declare(strict_types=1);

namespace Domain\PriceMap\Dtos;

use Spatie\LaravelData\Attributes\DataCollectionOf;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\DataCollection;
use Spatie\LaravelData\Optional;

final class PriceMapPricesUpdateDto extends Data
{
    /**
     * @param DataCollection<int,PriceMapPriceUpdateDto>|Optional $products
     * @param DataCollection<int,PriceMapPriceUpdateDto>|Optional $schema_options
     */
    public function __construct(
        #[DataCollectionOf(PriceMapPriceUpdateDto::class)]
        public DataCollection|Optional $products,
        #[DataCollectionOf(PriceMapPriceUpdateDto::class)]
        public DataCollection|Optional $schema_options,
    ) {}
}
