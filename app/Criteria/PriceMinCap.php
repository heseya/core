<?php

namespace App\Criteria;

use Brick\Money\Money;
use Domain\Price\Enums\ProductPriceType;
use Domain\SalesChannel\SalesChannelService;
use Heseya\Searchable\Criteria\Criterion;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\App;

class PriceMinCap extends Criterion
{
    public function query(Builder $query): Builder
    {
        if (!$this->value instanceof Money) {
            return $query;
        }

        /** @var Money $value */
        $value = $this->value;

        $salesChannel = App::make(SalesChannelService::class)->getCurrentRequestSalesChannel();

        return $query->whereHas(
            'pricesMin',
            fn (Builder $query) => $query
                ->where('gross', '>=', $value->getMinorAmount())
                ->where('currency', $value->getCurrency()->getCurrencyCode())
                ->where('price_type', ProductPriceType::PRICE_MIN->value)
                ->where('sales_channel_id', $salesChannel->id),
        );
    }
}
