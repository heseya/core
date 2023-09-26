<?php

namespace App\Criteria;

use Heseya\Searchable\Criteria\Criterion;
use Illuminate\Database\Eloquent\Builder;

class ShippingMethodSalesChannel extends Criterion
{
    public function query(Builder $query): Builder
    {
        return $query->whereHas('salesChannels', function (Builder $query): void {
            $query->where('sales_channel_id', '=', $this->value);
        });
    }
}
