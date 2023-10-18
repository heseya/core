<?php

declare(strict_types=1);

namespace Domain\Product\Dtos;

use Brick\Money\Currency;
use Brick\Money\Money;
use Domain\Currency\Currency as CurrencyEnum;
use Illuminate\Support\Str;
use Spatie\LaravelData\Attributes\Computed;
use Spatie\LaravelData\Attributes\WithCast;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Optional;
use Support\LaravelData\Casts\ArrayWrapCast;

final class ProductSearchDto extends Data
{
    #[Computed]
    public readonly ?string $price_sort_direction;

    #[Computed]
    public readonly ?string $price_sort_currency;

    /**
     * @param string[]|Optional $ids
     * @param string[]|Optional $sets
     * @param string[]|Optional $sets_not
     * @param string[]|Optional $tags
     * @param string[]|Optional $tags_not
     * @param string[]|Optional $attribute
     * @param string[]|Optional $attribute_not
     * @param string[]|Optional $metadata
     * @param string[]|Optional $metadata_public
     */
    public function __construct(
        public Optional|string|null $sort,
        public Optional|string|null $search,
        public array|Optional $ids,
        public Optional|string $slug,
        public Optional|string $name,
        public bool|Optional $public,
        public bool|Optional $available,
        public bool|Optional $all,
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
        if ($this->sort instanceof Optional) {
            $this->sort = null;
        }
        if ($match = Str::match('(price:.{3}:(?:asc|desc))', $this->sort ?? '')) {
            $price_sort = explode(':', $match);
            $this->price_sort_direction = $price_sort[0] . ':' . $price_sort[2];
            $this->price_sort_currency = $price_sort[1];
        } elseif ($match = Str::match('(price:(?:asc|desc))', $this->sort ?? '')) {
            $this->price_sort_direction = $match;
            $this->price_sort_currency = null;
        } else {
            $this->price_sort_direction = null;
            $this->price_sort_currency = null;
        }
    }

    public function toArray(): array
    {
        $result = parent::toArray();

        unset($result['price']);

        if ($this->price instanceof ProductSearchPriceDto) {
            if ($this->price->min instanceof Money) {
                $result['price_min'] = $this->price->min;
            }
            if ($this->price->max instanceof Money) {
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
            default => $this->price->currency,
        };
    }
}
