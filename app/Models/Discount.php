<?php

namespace App\Models;

use App\Criteria\DiscountSearch;
use App\Criteria\ForOrganizationDiscountSearch;
use App\Criteria\ForRoleDiscountSearch;
use App\Criteria\MetadataPrivateSearch;
use App\Criteria\MetadataSearch;
use App\Criteria\WhereHasCode;
use App\Criteria\WhereInIds;
use App\Enums\DiscountTargetType;
use App\Enums\DiscountType;
use App\Models\Contracts\SeoContract;
use App\Models\Interfaces\Translatable;
use App\Traits\CustomHasTranslations;
use App\Traits\HasMetadata;
use App\Traits\HasSeoMetadata;
use Domain\ProductSet\ProductSet;
use Domain\ShippingMethod\Models\ShippingMethod;
use Heseya\Searchable\Criteria\Like;
use Heseya\Searchable\Traits\HasCriteria;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * @property mixed $pivot
 * @property OrderDiscount|null $order_discount
 * @property DiscountType $type
 * @property DiscountTargetType $target_type
 * @property int $uses
 * @property ?int $orders_through_products_count
 *
 * @mixin IdeHelperDiscount
 */
class Discount extends Model implements SeoContract, Translatable
{
    use CustomHasTranslations;
    use HasCriteria;
    use HasFactory;
    use HasMetadata;
    use HasSeoMetadata;
    use SoftDeletes;

    public const HIDDEN_PERMISSION = 'sales.show_hidden';

    protected $fillable = [
        'name',
        'slug',
        'description',
        'description_html',
        'code',
        'target_type',
        'target_is_allow_list',
        'priority',
        'active',
        'percentage',
        'published',
    ];

    /** @var string[] */
    protected array $translatable = [
        'name',
        'description',
        'description_html',
    ];

    protected $casts = [
        'target_type' => DiscountTargetType::class,
        'target_is_allow_list' => 'boolean',
        'active' => 'boolean',
        'published' => 'array',
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
        'published' => Like::class,
        'discounts.published' => Like::class,
        'for_organization' => ForOrganizationDiscountSearch::class,
    ];

    public function scopeWithOrdersCount(Builder $query): Builder
    {
        return $query->withCount([
            'orders',
            'orderProducts as orders_through_products_count' => fn (Builder $subquery) => $subquery->select(DB::raw('count(distinct(order_id))')),
        ]);
    }

    /**
     * Counts unique Orders in which this Coupon was used.
     */
    protected function uses(): Attribute
    {
        return Attribute::get(fn (mixed $value, array $attributes = []) => match ($this->target_type) {
            DiscountTargetType::PRODUCTS, DiscountTargetType::CHEAPEST_PRODUCT => match (true) {
                $this->orders_through_products_count !== null => $this->orders_through_products_count,
                $this->relationLoaded('orderProducts') => $this->orderProducts->unique('order_id')->count(),
                default => $this->orderProducts()->distinct('order_id')->count('order_id'),
            },
            default => match (true) {
                $this->orders_count !== null => $this->orders_count,
                $this->relationLoaded('orders') => $this->orders->unique('id')->count(),
                default => $this->orders()->distinct('id')->count('id'),
            },
        });
    }

    public function orders(): MorphToMany
    {
        return $this->morphedByMany(Order::class, 'model', 'order_discounts')
            ->using(OrderDiscount::class)
            ->as('order_discount')
            ->withPivot([
                'name',
                'amount',
                'currency',
                'percentage',
                'target_type',
                'applied',
            ]);
    }

    public function orderProducts(): MorphToMany
    {
        return $this->morphedByMany(OrderProduct::class, 'model', 'order_discounts');
    }

    public function ordersThroughProducts(): Builder
    {
        return match (true) {
            $this->relationLoaded('orderProducts') => Order::query()->whereIn('id', $this->orderProducts->unique('order_id')->toArray()),
            default => Order::query()->whereHas('products', fn (Builder $subquery) => $subquery->whereHas('discounts', fn (Builder $subsubquery) => $subsubquery->where('id', $this->id))),
        };
    }

    public function ordersWithUses(): Builder
    {
        return match ($this->target_type) {
            DiscountTargetType::PRODUCTS, DiscountTargetType::CHEAPEST_PRODUCT => $this->ordersThroughProducts(),
            default => $this->orders()->getQuery(),
        };
    }

    public function products(): MorphToMany
    {
        return $this->morphedByMany(
            Product::class,
            'model',
            'model_has_discounts',
        )->with(['metadata', 'metadataPrivate', 'attributes', 'media', 'tags']);
    }

    public function productSets(): MorphToMany
    {
        return $this->morphedByMany(
            ProductSet::class,
            'model',
            'model_has_discounts',
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

    public function amounts(): MorphMany
    {
        return $this->morphMany(Price::class, 'model');
    }
}
