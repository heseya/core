<?php

namespace App\Criteria;

use Brick\Math\BigDecimal;
use Brick\Money\Money;
use Domain\Price\Enums\ProductPriceType;
use Domain\SalesChannel\SalesChannelRepository;
use Heseya\Searchable\Criteria\Criterion;
use Illuminate\Database\Eloquent\Builder;

class PriceMaxCap extends Criterion
{
    public function query(Builder $query): Builder
    {
        if (!$this->value instanceof Money) {
            return $query;
        }

        /** @var Money $value */
        $value = $this->value;

        $salesChannel = request()->header('X-Sales-Channel')
            ? app(SalesChannelRepository::class)->getOne(request()->header('X-Sales-Channel'))
            : app(SalesChannelRepository::class)->getDefault();

        $value = $value->dividedBy(BigDecimal::of($salesChannel->vat_rate)->multipliedBy(0.01)->plus(1));

        return $query->whereHas('pricesMax',
            fn (Builder $query) => $query
                ->where('value', '<=', $value->getMinorAmount())
                ->where('currency', $value->getCurrency()->getCurrencyCode())
                ->where('price_type', ProductPriceType::PRICE_MAX->value),
        );
    }
}
