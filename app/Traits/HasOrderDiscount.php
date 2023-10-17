<?php

namespace App\Traits;

use App\Models\Discount;
use App\Models\Model;
use App\Models\OrderDiscount;
use Illuminate\Database\Eloquent\Relations\MorphToMany;

trait HasOrderDiscount
{
    public function discounts(): MorphToMany
    {
        assert($this instanceof Model);

        return $this->morphToMany(Discount::class, 'model', 'order_discounts')
            ->using(OrderDiscount::class)
            ->as('order_discount')
            ->withPivot([
                'name',
                'amount',
                'currency',
                'percentage',
                'target_type',
                'applied',
            ])
            ->withTrashed();
    }
}
