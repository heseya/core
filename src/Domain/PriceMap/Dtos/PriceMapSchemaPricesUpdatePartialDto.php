<?php

declare(strict_types=1);

namespace Domain\PriceMap\Dtos;

use Spatie\LaravelData\Attributes\DataCollectionOf;
use Spatie\LaravelData\Attributes\Validation\Exists;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\DataCollection;

final class PriceMapSchemaPricesUpdatePartialDto extends Data
{
    /**
     * @param DataCollection<int,PriceMapSchemaPricesUpdateOptionDto> $options
     */
    public function __construct(
        #[Required(), Exists('price_maps', 'id')]
        public string $price_map_id,
        #[DataCollectionOf(PriceMapSchemaPricesUpdateOptionDto::class)]
        public DataCollection $options,
    ) {}
}
