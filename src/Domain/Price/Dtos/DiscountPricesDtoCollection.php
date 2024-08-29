<?php

declare(strict_types=1);

namespace Domain\Price\Dtos;

use Domain\Price\Enums\DiscountConditionPriceType;
use Illuminate\Support\Arr;
use Illuminate\Support\Enumerable;
use Spatie\LaravelData\DataCollection;

/**
 * @extends DataCollection<int,DiscountPricesDto>
 */
final class DiscountPricesDtoCollection extends DataCollection
{
    /**
     * @param array<value-of<DiscountConditionPriceType>,PriceDto[]>|DataCollection<int,DiscountPricesDto>|DiscountPricesDto[] $items
     */
    public function __construct(
        string $dataClass = DiscountPricesDto::class,
        array|DataCollection|Enumerable|null $items = null,
    ) {
        $dataClass = DiscountPricesDto::class;

        if (is_array($items)) {
            $items = Arr::has($items, ['type', 'prices'])
                ? [$items]
                : Arr::map($items, fn ($value, $key) => [
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
