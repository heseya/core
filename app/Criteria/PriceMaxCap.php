<?php

namespace App\Criteria;

use App\Models\Product;
use Brick\Money\Money;
use Domain\Price\Enums\ProductPriceType;
use Domain\SalesChannel\SalesChannelService;
use Heseya\Searchable\Criteria\Criterion;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\App;

class PriceMaxCap extends Criterion
{
    /**
     * @param Builder<Product> $query
     *
     * @return Builder<Product>
     */
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
                ->where('net', '<=', $value->getMinorAmount())
                ->where('currency', $value->getCurrency()->getCurrencyCode())
                ->where('price_type', ProductPriceType::PRICE_MIN->value)
                ->where('sales_channel_id', $salesChannel->id),
        );
    }
}
