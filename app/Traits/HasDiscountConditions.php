<?php

namespace App\Traits;

use App\Models\DiscountCondition;
use Illuminate\Database\Eloquent\Relations\MorphToMany;

trait HasDiscountConditions
{
    public function discountConditions(): MorphToMany
    {
        return $this->morphToMany(DiscountCondition::class, 'model', 'model_has_discount_conditions');
    }
}
