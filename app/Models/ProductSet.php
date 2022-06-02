<?php

namespace App\Models;

use App\Criteria\MetadataPrivateSearch;
use App\Criteria\MetadataSearch;
use App\Criteria\ProductSetSearch;
use App\Enums\DiscountTargetType;
use App\Traits\HasDiscountConditions;
use App\Traits\HasDiscounts;
use App\Traits\HasMetadata;
use App\Traits\HasSeoMetadata;
use Heseya\Searchable\Criteria\Like;
use Heseya\Searchable\Traits\HasCriteria;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * @mixin IdeHelperProductSet
 */
class ProductSet extends Model
{
    use HasCriteria, HasFactory, SoftDeletes, HasSeoMetadata, HasMetadata, HasDiscountConditions, HasDiscounts;

    protected $fillable = [
        'name',
        'slug',
        'public',
        'public_parent',
        'order',
        'hide_on_index',
        'parent_id',
        'description_html',
        'cover_id',
    ];

    protected $casts = [
        'public' => 'boolean',
        'public_parent' => 'boolean',
        'hide_on_index' => 'boolean',
    ];

    protected array $criteria = [
        'name' => Like::class,
        'slug' => Like::class,
        'search' => ProductSetSearch::class,
        'public',
        'metadata' => MetadataSearch::class,
        'metadata_private' => MetadataPrivateSearch::class,
        'parent_id',
    ];

    public function getSlugOverrideAttribute(): bool
    {
        return $this->parent !== null && !Str::startsWith(
            $this->slug,
            $this->parent->slug . '-',
        );
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function getSlugSuffixAttribute(): string
    {
        return $this->slugOverride || $this->parent === null ? $this->slug :
            Str::after($this->slug, $this->parent->slug . '-');
    }

    public function scopePublic(Builder $query): Builder
    {
        return $query->where('public', true)->where('public_parent', true);
    }

    public function scopeRoot(Builder $query): Builder
    {
        return $query->whereNull('parent_id');
    }

    public function scopeReversed(Builder $query): Builder
    {
        return $query->withoutGlobalScope('ordered')
            ->orderBy('order', 'desc');
    }

    public function allChildren(): HasMany
    {
        return $this->children()->with([
            'allChildren',
            'metadata',
            'metadataPrivate',
            'attributes',
            'parent',
        ]);
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    public function attributes(): BelongsToMany
    {
        return $this->belongsToMany(Attribute::class);
    }

    public function allChildrenPublic(): HasMany
    {
        return $this->childrenPublic()->with([
            'allChildrenPublic',
            'metadata',
            'attributes',
            'parent',
        ]);
    }

    public function childrenPublic(): HasMany
    {
        return $this->children()->public();
    }

    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'product_set_product')->withPivot('order');
    }

    public function media(): HasOne
    {
        return $this->hasOne(Media::class, 'id', 'cover_id');
    }

    public function allProductsSales(): Collection
    {
        $sales = $this
            ->discounts()
            ->with(['products', 'productSets', 'conditionGroups', 'shippingMethods'])
            ->where('code', '=', null)
            ->where('target_type', '=', DiscountTargetType::PRODUCTS)
            ->get();

        if ($this->parent) {
            $sales = $sales->merge($this->parent->allProductsSales());
        }

        return $sales->unique('id');
    }

    public function allProducts(): Collection
    {
        $products = $this->products()->get();

        foreach ($this->children()->get() as $child) {
            $products = $products->merge($child->allProducts());
        }

        return $products->unique('id');
    }

    protected static function booted(): void
    {
        static::addGlobalScope(
            'ordered',
            fn (Builder $builder) => $builder->orderBy('product_sets.order'),
        );
    }
}
