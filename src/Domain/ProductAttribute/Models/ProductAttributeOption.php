<?php

declare(strict_types=1);

namespace Domain\ProductAttribute\Models;

use App\Models\ProductAttribute;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Pivot;

/**
 * @mixin IdeHelperProductAttributeOption
 */
final class ProductAttributeOption extends Pivot
{
    protected $table = 'product_attribute_attribute_option';

    /**
     * @return BelongsTo<AttributeOption,self>
     */
    public function attributeOption(): BelongsTo
    {
        return $this->belongsTo(AttributeOption::class, 'attribute_option_id');
    }

    /**
     * @return BelongsTo<ProductAttribute,self>
     */
    public function productAttribute(): BelongsTo
    {
        return $this->belongsTo(ProductAttribute::class, 'product_attribute_id');
    }
}
