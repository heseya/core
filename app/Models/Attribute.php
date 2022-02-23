<?php

namespace App\Models;

use App\Enums\AttributeType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @mixin IdeHelperAttribute
 */
class Attribute extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'type',
        'global',
        'sortable',
    ];

    protected $casts = [
        'type' => AttributeType::class,
        'global' => 'boolean',
        'sortable' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function options(): HasMany
    {
        return $this->hasMany(AttributeOption::class);
    }

    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'product_attribute')
            ->withPivot('option_id')
            ->using(ProductAttribute::class);
    }

    public function productSets(): BelongsToMany
    {
        return $this->belongsToMany(ProductSet::class);
    }
}
