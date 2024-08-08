<?php

declare(strict_types=1);

namespace Domain\PriceMap\Dtos;

use Spatie\LaravelData\Attributes\DataCollectionOf;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\DataCollection;

final class PriceMapSchemaPricesUpdateDto extends Data
{
    /**
     * @param DataCollection<int,PriceMapSchemaPricesUpdatePartialDto> $prices
     */
    public function __construct(
        #[DataCollectionOf(PriceMapSchemaPricesUpdatePartialDto::class)]
        public DataCollection $prices,
    ) {}
}
