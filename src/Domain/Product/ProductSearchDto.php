<?php

declare(strict_types=1);

namespace Domain\Product;

use Brick\Money\Currency;
use Brick\Money\Money;
use Domain\Currency\Currency as CurrencyEnum;
use Illuminate\Support\Str;
use Spatie\LaravelData\Attributes\WithCast;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Optional;
use Support\DtoCasts\ArrayWrapCast;

final class ProductSearchDto extends Data
{
    public function __construct(
        public Optional|string|null $sort,
        public Optional|string|null $search,
        public array|Optional $ids,
        public Optional|string $slug,
        public Optional|string $name,
        public bool|Optional $public,
        public bool|Optional $available,
        public bool|Optional $has_cover,
        public bool|Optional $has_items,
        public bool|Optional $has_schemas,
        public bool|Optional $shipping_digital,
        #[WithCast(ArrayWrapCast::class)]
        public array|Optional $sets,
        #[WithCast(ArrayWrapCast::class)]
        public array|Optional $sets_not,
        #[WithCast(ArrayWrapCast::class)]
        public array|Optional $tags,
        #[WithCast(ArrayWrapCast::class)]
        public array|Optional $tags_not,
        #[WithCast(ArrayWrapCast::class)]
        public array|Optional $attribute,
        #[WithCast(ArrayWrapCast::class)]
        public array|Optional $attribute_not,
        #[WithCast(ArrayWrapCast::class)]
        public array|Optional $metadata,
        #[WithCast(ArrayWrapCast::class)]
        public array|Optional $metadata_public,
        public Optional|ProductSearchPriceDto $price,
    ) {
        $this->sort = is_string($sort)
            ? Str::replace(
                [
                    'price:asc',
                    'price:desc',
                ],
                [
                    'price_min:asc',
                    'price_max:desc',
                ],
                $sort
            )
            : null;
    }

    public function toArray(): array
    {
        $result = parent::toArray();

        unset($result['price']);

        if ($this->price instanceof ProductSearchPriceDto) {
            if ($this->price->min instanceof Money) {
                $result['price_min'] = $this->price->min;
            }
            if ($this->price->min instanceof Money) {
                $result['price_max'] = $this->price->max;
            }
        }

        return $result;
    }

    public function getCurrency(): Currency
    {
        return match (true) {
            $this->price instanceof Optional,
            $this->price->currency instanceof Optional => CurrencyEnum::DEFAULT->toCurrencyInstance(),
            default => $this->price->currency
        };
    }
}
