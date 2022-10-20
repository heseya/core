<?php

namespace App\Traits;

use App\Models\Discount;
use Illuminate\Database\Eloquent\Relations\MorphToMany;

trait HasDiscounts
{
    public function discounts(): MorphToMany
    {
        return $this
            ->morphToMany(Discount::class, 'model', 'model_has_discounts')
            ->with(['products', 'productSets', 'conditionGroups', 'shippingMethods']);
    }
}
