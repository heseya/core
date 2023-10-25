<?php

declare(strict_types=1);

namespace Domain\ProductSet;

use App\Criteria\MetadataPrivateSearch;
use App\Criteria\MetadataSearch;
use App\Criteria\ParentIdSearch;
use App\Criteria\ProductSetSearch;
use App\Criteria\WhereInIds;
use App\Enums\DiscountTargetType;
use App\Models\Contracts\SeoContract;
use App\Models\Discount;
use App\Models\Interfaces\Translatable;
use App\Models\Media;
use App\Models\Model;
use App\Models\Product;
use App\Traits\CustomHasTranslations;
use App\Traits\HasDiscountConditions;
use App\Traits\HasDiscounts;
use App\Traits\HasMetadata;
use App\Traits\HasSeoMetadata;
use Domain\ProductAttribute\Models\Attribute;
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
final class ProductSet extends Model implements SeoContract, Translatable
{
    use CustomHasTranslations;
    use HasCriteria;
    use HasDiscountConditions;
    use HasDiscounts;
    use HasFactory;
    use HasMetadata;
    use HasSeoMetadata;
    use SoftDeletes;

    public const HIDDEN_PERMISSION = 'product_sets.show_hidden';
    public int|null $depth = null;

    protected $fillable = [
        'id',
        'name',
        'slug',
        'public',
        'public_parent',
        'order',
        'parent_id',
        'description_html',
        'cover_id',
        'published',
    ];

    /** @var string[] */
    protected array $translatable = [
        'name',
        'description_html',
    ];

    protected $casts = [
        'public' => 'boolean',
        'public_parent' => 'boolean',
        'published' => 'array',
    ];

    /** @var string[] */
    protected array $criteria = [
        'name' => Like::class,
        'slug' => Like::class,
        'search' => ProductSetSearch::class,
        'public',
        'metadata' => MetadataSearch::class,
        'metadata_private' => MetadataPrivateSearch::class,
        'parent_id' => ParentIdSearch::class,
        'ids' => WhereInIds::class,
        'published' => Like::class,
        'product_sets.published' => Like::class,
    ];

    public function getSlugOverrideAttribute(): bool
    {
        return $this->parent !== null && !Str::startsWith(
            $this->slug,
            $this->parent->slug . '-',
        );
    }

    /**
     * @return BelongsTo<self, self>
     */
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

    /**
     * @return HasMany<self>
     */
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

    /**
     * @return Collection<self>
     */
    public function getChildren(int $depth = 0): Collection
    {
        $children = collect([]);
        if ($depth > 0) {
            --$depth;

            foreach ($this->children as $child) {
                $child->depth = $depth;
                $children->push($child);
            }
        }

        return $children;
    }

    /**
     * @return Collection<self>
     */
    public function getPublicChildren(int $depth = 0): Collection
    {
        $children = collect([]);
        if ($depth > 0) {
            --$depth;

            foreach ($this->childrenPublic as $child) {
                $child->depth = $depth;
                $children->push($child);
            }
        }

        return $children;
    }

    /**
     * @return HasMany<self>
     */
    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    /**
     * @return BelongsToMany<Attribute>
     */
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

    /**
     * @return HasMany<self>
     */
    public function childrenPublic(): HasMany
    {
        return $this->children()->public();
    }

    /**
     * @return BelongsToMany<Product>
     */
    public function products(): BelongsToMany
    {
        return $this
            ->belongsToMany(Product::class, 'product_set_product')
            ->withPivot('order')
            ->orderByPivot('order');
    }

    public function relatedProducts(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'related_product_sets');
    }

    public function allProductsIds(): Collection
    {
        $products = $this->products()->pluck('id');

        foreach ($this->children as $child) {
            $products = $products->merge($child->allProductsIds());
        }

        return $products->unique();
    }

    /**
     * @return HasOne<Media>
     */
    public function media(): HasOne
    {
        return $this->hasOne(Media::class, 'id', 'cover_id');
    }

    public function allProductsSales(): Collection
    {
        $sales = $this->discounts->filter(
            fn (Discount $discount): bool => $discount->code === null
                && $discount->active
                && $discount->target_type->is(DiscountTargetType::PRODUCTS),
        );

        if ($this->parent) {
            $sales = $sales->merge($this->parent->allProductsSales());
        }

        return $sales->unique('id');
    }

    public function allChildrenIds(string $relation): Collection
    {
        $result = $this->{$relation}->pluck('id');

        foreach ($this->{$relation} as $child) {
            $result = $result->merge($child->allChildrenIds($relation));
        }

        return $result->unique();
    }

    protected static function booted(): void
    {
        self::addGlobalScope(
            'ordered',
            fn (Builder $builder) => $builder
                ->orderBy('product_sets.order')
                ->orderBy('product_sets.created_at')
                ->orderBy('product_sets.updated_at'),
        );
    }
}
