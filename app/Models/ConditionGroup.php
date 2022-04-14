<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ConditionGroup extends Model
{
    public function discounts(): BelongsToMany
    {
        return $this->belongsToMany(Discount::class, 'discount_condition_groups');
    }

    public function conditions(): HasMany
    {
        return $this->hasMany(DiscountCondition::class);
    }
}
