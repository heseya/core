<?php

namespace App\Models;

use App\Criteria\MetadataPrivateSearch;
use App\Criteria\MetadataSearch;
use App\Criteria\ParentIdSearch;
use App\Criteria\ProductSetSearch;
use App\Criteria\WhereInIds;
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
 * @property mixed $pivot
 *
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
        'parent_id',
        'description_html',
        'cover_id',
    ];

    protected $casts = [
        'public' => 'boolean',
        'public_parent' => 'boolean',
    ];

    protected array $criteria = [
        'name' => Like::class,
        'slug' => Like::class,
        'search' => ProductSetSearch::class,
        'public',
        'metadata' => MetadataSearch::class,
        'metadata_private' => MetadataPrivateSearch::class,
        'parent_id' => ParentIdSearch::class,
        'ids' => WhereInIds::class,
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
        return $this
            ->belongsToMany(Product::class, 'product_set_product')
            ->withPivot('order')
            ->orderByPivot('order');
    }

    public function allProductsIds(): Collection
    {
        $products = $this->products()->pluck('id');

        foreach ($this->children as $child) {
            $products = $products->merge($child->allProductsIds());
        }

        return $products->unique();
    }

    public function media(): HasOne
    {
        return $this->hasOne(Media::class, 'id', 'cover_id');
    }

    public function allProductsSales(): Collection
    {
        $sales = $this->discounts
            ->filter(fn ($discount): bool => $discount->code === null
                && $discount->active
                && $discount->target_type->is(DiscountTargetType::PRODUCTS));

        if ($this->parent) {
            $sales = $sales->merge($this->parent->allProductsSales());
        }

        return $sales->unique('id');
    }

    protected static function booted(): void
    {
        static::addGlobalScope(
            'ordered',
            fn (Builder $builder) => $builder->orderBy('product_sets.order'),
        );
    }

    public function allChildrenIds(string $relation): Collection
    {
        $result = $this->$relation->pluck('id');

        foreach ($this->$relation as $child) {
            $result = $result->merge($child->allChildrenIds($relation));
        }

        return $result->unique();
    }
}
