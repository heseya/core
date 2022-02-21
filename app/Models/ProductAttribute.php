<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Pivot;

/**
 * @mixin IdeHelperProductAttribute
 */
class ProductAttribute extends Pivot
{
    public function option(): BelongsTo
    {
        return $this->belongsTo(AttributeOption::class);
    }
}
