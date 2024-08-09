<?php

declare(strict_types=1);

namespace Domain\PriceMap\Dtos;

use Spatie\LaravelData\Attributes\DataCollectionOf;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\DataCollection;

final class PriceMapProductPricesUpdateDto extends Data
{
    /**
     * @param DataCollection<int,PriceMapProductPricesUpdatePartialDto> $prices
     */
    public function __construct(
        #[DataCollectionOf(PriceMapProductPricesUpdatePartialDto::class)]
        public DataCollection $prices,
    ) {}
}
