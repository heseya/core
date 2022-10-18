<?php

namespace App\Criteria;

use Heseya\Searchable\Criteria\Criterion;
use Illuminate\Database\Eloquent\Builder;

class DepositSearch extends Criterion
{
    public function query(Builder $query): Builder
    {
        return $query->where(function (Builder $query): void {
            $query
                ->orWhere('created_at', 'LIKE', '%' . $this->value . '%')
                ->orWhere('shipping_date', 'LIKE', '%' . $this->value . '%')
                ->orWhereHas('item', function (Builder $query): void {
                    $query->where('name', 'LIKE', '%' . $this->value . '%')
                        ->orWhere('sku', 'LIKE', '%' . $this->value . '%');
                });
        });
    }
}
