<?php

namespace App\Models;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\Pivot;

/**
 * @mixin IdeHelperProductAttribute
 */
class ProductAttribute extends Pivot
{
    use HasUuid;

    public function options(): BelongsToMany
    {
        return $this->belongsToMany(
            AttributeOption::class,
            'product_attribute_attribute_option',
            'product_attribute_id',
            'attribute_option_id',
        );
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function attribute(): BelongsTo
    {
        return $this->belongsTo(Attribute::class);
    }
}
