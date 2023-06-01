<?php

namespace App\Traits;

use App\Models\Discount;
use App\Models\OrderDiscount;
use Illuminate\Database\Eloquent\Relations\MorphToMany;

trait HasOrderDiscount
{
    public function discounts(): MorphToMany
    {
        return $this->morphToMany(Discount::class, 'model', 'order_discounts')
            ->withPivot(['type', 'value', 'name', 'code', 'target_type', 'applied_discount'])
            ->using(OrderDiscount::class)
            ->withTrashed();
    }
}
