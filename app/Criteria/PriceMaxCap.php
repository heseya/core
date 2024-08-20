<?php

namespace App\Criteria;

use Brick\Math\BigDecimal;
use Brick\Math\RoundingMode;
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

        $value = $value->dividedBy(BigDecimal::of($salesChannel->vat_rate)->multipliedBy(0.01)->plus(1), roundingMode: RoundingMode::HALF_DOWN);

        return $query->whereHas(
            'pricesMax',
            fn (Builder $query) => $query
                ->where('value', '<=', $value->getMinorAmount())
                ->where('currency', $value->getCurrency()->getCurrencyCode())
                ->where('price_type', ProductPriceType::PRICE_MAX->value)
                ->where('price_map_id', $salesChannel->price_map_id),
        );
    }
}
