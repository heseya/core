<?php

namespace App\Models;

use App\Criteria\DiscountSearch;
use App\Criteria\MetadataPrivateSearch;
use App\Criteria\MetadataSearch;
use App\Criteria\WhereHasCode;
use App\Enums\DiscountTargetType;
use App\Enums\DiscountType;
use App\Traits\HasMetadata;
use Heseya\Searchable\Criteria\Like;
use Heseya\Searchable\Traits\HasCriteria;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Auditable;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;

/**
 * @mixin IdeHelperDiscount
 */
class Discount extends Model implements AuditableContract
{
    use HasFactory, HasCriteria, SoftDeletes, Auditable, HasMetadata;

    protected $fillable = [
        'name',
        'description',
        'code',
        'value',
        'type',
        'target_type',
        'target_is_allow_list',
        'priority',
    ];

    protected $casts = [
        'type' => DiscountType::class,
        'target_type' => DiscountTargetType::class,
        'target_is_allow_list' => 'boolean',
    ];

    protected array $criteria = [
        'description' => Like::class,
        'code' => Like::class,
        'search' => DiscountSearch::class,
        'metadata' => MetadataSearch::class,
        'metadata_private' => MetadataPrivateSearch::class,
        'coupon' => WhereHasCode::class,
    ];

    public function getUsesAttribute(): int
    {
        return $this->orders->count();
    }

    public function orders(): MorphToMany
    {
        return $this->morphedByMany(Order::class, 'model', 'order_discounts');
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
}
