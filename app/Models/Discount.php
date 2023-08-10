<?php

namespace App\Models;

use App\Criteria\DiscountSearch;
use App\Criteria\ForRoleDiscountSearch;
use App\Criteria\MetadataPrivateSearch;
use App\Criteria\MetadataSearch;
use App\Criteria\WhereHasCode;
use App\Criteria\WhereInIds;
use App\Enums\DiscountTargetType;
use App\Enums\DiscountType;
use App\Traits\HasMetadata;
use App\Traits\HasSeoMetadata;
use Heseya\Searchable\Criteria\Like;
use Heseya\Searchable\Traits\HasCriteria;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Collection;
use OwenIt\Auditing\Auditable;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;

/**
 * @property mixed $pivot
 * @property DiscountType $type
 * @property DiscountTargetType $target_type
 *
 * @mixin IdeHelperDiscount
 */
class Discount extends Model implements AuditableContract
{
    use HasFactory;
    use HasCriteria;
    use SoftDeletes;
    use Auditable;
    use HasMetadata;
    use HasSeoMetadata;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'description_html',
        'code',
        'value',
        'type',
        'target_type',
        'target_is_allow_list',
        'priority',
        'active',
    ];

    protected $casts = [
        'type' => DiscountType::class,
        'target_type' => DiscountTargetType::class,
        'target_is_allow_list' => 'boolean',
        'active' => 'boolean',
    ];

    protected array $criteria = [
        'description' => Like::class,
        'code' => Like::class,
        'search' => DiscountSearch::class,
        'metadata' => MetadataSearch::class,
        'metadata_private' => MetadataPrivateSearch::class,
        'coupon' => WhereHasCode::class,
        'for_role' => ForRoleDiscountSearch::class,
        'ids' => WhereInIds::class,
    ];

    public function getUsesAttribute(): int
    {
        return $this->orders->count();
    }

    public function orders(): MorphToMany
    {
        return $this->morphedByMany(Order::class, 'model', 'order_discounts');
    }

    public function ordersWithUses(): Builder
    {
        $orders = $this->orders->pluck('id');
        $productIds = $this->allProductsIds();
        $discountId = $this->getKey();
        $usesOrders = Order::query()
            ->with(['products', 'products.discounts', 'products.product'])
            ->whereHas('products.discounts', static function (Builder $query) use ($discountId): void {
                $query->where('id', $discountId);
            })
            ->whereHas('products.product', static function (Builder $query) use($productIds): void {
                $query->whereIn('id',  $productIds);
            })
            ->whereNotIn('id', $orders)
            ->pluck('id');

        return Order::query()->whereIn('id', [...$orders, ...$usesOrders]);
    }

    public function products(): MorphToMany
    {
        return $this->morphedByMany(
            Product::class,
            'model',
            'model_has_discounts'
        )->with(['metadata', 'metadataPrivate', 'attributes', 'media', 'tags']);
    }

    public function productSets(): MorphToMany
    {
        return $this->morphedByMany(
            ProductSet::class,
            'model',
            'model_has_discounts'
        )->with(['metadata', 'metadataPrivate']);
    }

    public function shippingMethods(): MorphToMany
    {
        return $this->morphedByMany(
            ShippingMethod::class,
            'model',
            'model_has_discounts',
        )->with(['metadata', 'metadataPrivate']);
    }

    public function conditionGroups(): BelongsToMany
    {
        return $this->belongsToMany(ConditionGroup::class, 'discount_condition_groups');
    }

    public function allProductsIds(): Collection
    {
        $products = $this->products->pluck('id');

        foreach ($this->productSets()->get() as $productSet) {
            $products = $products->merge($productSet->allProductsIds());
        }

        return $products->unique();
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('active', true);
    }
}
