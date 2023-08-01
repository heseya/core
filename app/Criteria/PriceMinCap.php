<?php

namespace App\Criteria;

use App\Enums\Product\ProductPriceType;
use Brick\Money\Money;
use Heseya\Searchable\Criteria\Criterion;
use Illuminate\Database\Eloquent\Builder;

class PriceMinCap extends Criterion
{
    public function query(Builder $query): Builder
    {
        /** @var Money $value */
        $value = $this->value;

        return $query->whereHas('pricesMin',
            fn (Builder $query) => $query
                ->where('value', '>=', $value->getMinorAmount())
                ->where('currency', $value->getCurrency()->getCurrencyCode())
                ->where('price_type', ProductPriceType::PRICE_MIN)
        );
    }
}
