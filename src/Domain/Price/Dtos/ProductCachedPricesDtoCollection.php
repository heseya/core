<?php

declare(strict_types=1);

namespace Domain\Price\Dtos;

use Domain\Price\Enums\ProductPriceType;
use Illuminate\Support\Arr;
use Illuminate\Support\Enumerable;
use Spatie\LaravelData\DataCollection;

/**
 * @extends DataCollection<int,ProductCachedPricesDto>
 */
final class ProductCachedPricesDtoCollection extends DataCollection
{
    /**
     * @param array<value-of<ProductPriceType>,ProductCachedPriceDto[]>|DataCollection<int,ProductCachedPricesDto>|ProductCachedPricesDto[] $items
     */
    public function __construct(
        string $dataClass = ProductCachedPricesDto::class,
        array|DataCollection|Enumerable|null $items = null,
    ) {
        $dataClass = ProductCachedPricesDto::class;

        if (is_array($items)) {
            $items = Arr::has($items, ['type', 'prices'])
                ? $items
                : Arr::mapWithKeys($items, fn ($value, $key) => [
                    'type' => $key,
                    'prices' => $value,
                ]);
        }

        parent::__construct(
            $dataClass,
            $items,
        );
    }
}
