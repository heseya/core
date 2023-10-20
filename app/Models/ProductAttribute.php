<?php

namespace App\Models;

use App\Traits\HasUuid;
use Domain\ProductAttribute\Models\Attribute;
use Domain\ProductAttribute\Models\AttributeOption;
use Domain\ProductAttribute\Models\ProductAttributeOption;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\Pivot;

/**
 * @property ProductAttributeOption|null $product_attribute_option_pivot
 *
 * @mixin IdeHelperProductAttribute
 */
class ProductAttribute extends Pivot
{
    use HasUuid;

    protected $table = 'product_attribute';

    protected $primaryKey = 'pivot_id';

    public $timestamps = false;

    public function options(): BelongsToMany
    {
        return $this->belongsToMany(
            AttributeOption::class,
            'product_attribute_attribute_option',
            'product_attribute_id',
            'attribute_option_id',
            'pivot_id',
            'id',
        )->using(ProductAttributeOption::class)
            ->as('product_attribute_option_pivot');
    }

    public function productAttributeOptions(): HasMany
    {
        return $this->hasMany(ProductAttributeOption::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function attribute(): BelongsTo
    {
        return $this->belongsTo(Attribute::class);
    }

    public function scopeSlug(Builder $query, array|string $slug): void
    {
        $query->whereHas('attribute', fn (Builder $subquery) => $subquery->whereIn('slug', (array) $slug));
    }
}
