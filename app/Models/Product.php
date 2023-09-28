<?php

namespace App\Models;

use App\Criteria\MetadataPrivateSearch;
use App\Criteria\MetadataSearch;
use App\Criteria\PriceMaxCap;
use App\Criteria\PriceMinCap;
use App\Criteria\ProductAttributeSearch;
use App\Criteria\ProductNotAttributeSearch;
use App\Criteria\ProductSearch;
use App\Criteria\TranslatedLike;
use App\Criteria\WhereHasId;
use App\Criteria\WhereHasItems;
use App\Criteria\WhereHasPhoto;
use App\Criteria\WhereHasSchemas;
use App\Criteria\WhereHasSlug;
use App\Criteria\WhereInIds;
use App\Criteria\WhereNotId;
use App\Criteria\WhereNotSlug;
use App\Enums\DiscountTargetType;
use App\Models\Contracts\SeoContract;
use App\Models\Contracts\SortableContract;
use App\Models\Interfaces\Translatable;
use App\SortColumnTypes\PriceColumn;
use App\SortColumnTypes\TranslatedColumn;
use App\Traits\CustomHasTranslations;
use App\Traits\HasDiscountConditions;
use App\Traits\HasDiscounts;
use App\Traits\HasMediaAttachments;
use App\Traits\HasMetadata;
use App\Traits\HasSeoMetadata;
use App\Traits\Sortable;
use Domain\Page\Page;
use Domain\Price\Enums\ProductPriceType;
use Domain\Product\Enums\ProductSalesChannelStatus;
use Domain\Product\Models\ProductSalesChannel;
use Domain\ProductAttribute\Models\Attribute;
use Domain\ProductSet\ProductSet;
use Domain\SalesChannel\Models\SalesChannel;
use Domain\SalesChannel\SalesChannelRepository;
use Domain\Tag\Models\Tag;
use Heseya\Searchable\Criteria\Equals;
use Heseya\Searchable\Criteria\Like;
use Heseya\Searchable\Traits\HasCriteria;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Config;

/**
 * @property string $name
 * @property string $description_html
 * @property string $description_short
 * @property mixed $pivot
 * @property Collection<int, Price> $pricesBase
 *
 * @mixin IdeHelperProduct
 */
class Product extends Model implements SeoContract, SortableContract, Translatable
{
    use CustomHasTranslations;
    use HasCriteria;
    use HasDiscountConditions;
    use HasDiscounts;
    use HasFactory;
    use HasMediaAttachments;
    use HasMetadata;
    use HasSeoMetadata;
    use SoftDeletes;
    use Sortable;

    public const HIDDEN_PERMISSION = 'products.show_hidden';

    protected $fillable = [
        'id',
        'name',
        'slug',
        'description_html',
        'description_short',
        'quantity_step',
        'google_product_category',
        'available',
        'order',
        'shipping_time',
        'shipping_date',
        'has_schemas',
        'quantity',
        'shipping_digital',
        'purchase_limit_per_user',
        'published',
    ];

    protected array $translatable = [
        'name',
        'description_html',
        'description_short',
    ];

    protected $casts = [
        'shipping_date' => 'date',
        'available' => 'bool',
        'quantity_step' => 'float',
        'published' => 'array',
        'has_schemas' => 'bool',
        'quantity' => 'float',
        'shipping_digital' => 'bool',
        'purchase_limit_per_user' => 'float',
    ];

    protected array $sortable = [
        'id',
        'name' => TranslatedColumn::class,
        'created_at',
        'updated_at',
        'order',
        'available',
        'attribute.*',
        'set.*',
        'price' => PriceColumn::class,
    ];

    protected array $criteria = [
        'search' => ProductSearch::class,
        'ids' => WhereInIds::class,
        'slug' => Like::class,
        'name' => TranslatedLike::class,
        'available' => Equals::class,
        'sets' => WhereHasSlug::class,
        'sets_not' => WhereNotSlug::class,
        'tags' => WhereHasId::class,
        'tags_not' => WhereNotId::class,
        'metadata' => MetadataSearch::class,
        'metadata_private' => MetadataPrivateSearch::class,
        'attribute' => ProductAttributeSearch::class,
        'attribute_not' => ProductNotAttributeSearch::class,
        'has_cover' => WhereHasPhoto::class,
        'has_items' => WhereHasItems::class,
        'has_schemas' => WhereHasSchemas::class,
        'shipping_digital' => Equals::class,
        'price_min' => PriceMinCap::class,
        'price_max' => PriceMaxCap::class,
        'published' => Like::class,
        'products.published' => Like::class,
    ];

    protected string $defaultSortBy = 'products.order';
    protected string $defaultSortDirection = 'desc';

    public function sets(): BelongsToMany
    {
        return $this
            ->belongsToMany(ProductSet::class, 'product_set_product')
            ->withPivot('order');
    }

    public function relatedSets(): BelongsToMany
    {
        return $this
            ->belongsToMany(ProductSet::class, 'related_product_sets');
    }

    public function items(): BelongsToMany
    {
        return $this
            ->belongsToMany(Item::class, 'item_product')
            ->withPivot('required_quantity')
            ->using(ItemProduct::class);
    }

    public function media(): BelongsToMany
    {
        return $this
            ->belongsToMany(Media::class, 'product_media')
            ->orderByPivot('order');
    }

    public function orders(): BelongsToMany
    {
        return $this
            ->belongsToMany(Order::class)
            ->using(OrderProduct::class);
    }

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class, 'product_tags');
    }

    public function publishedTags(): BelongsToMany
    {
        return !Config::get('translatable.fallback_locale')
            ? $this->tags()->where('tags.published', 'LIKE', '%' . Config::get('language.id') . '%') : $this->tags();
    }

    public function requiredSchemas(): BelongsToMany
    {
        return $this->schemas()->where('required', true);
    }

    public function schemas(): BelongsToMany
    {
        return $this
            ->belongsToMany(Schema::class, 'product_schemas')
            ->with(['options', 'metadata', 'metadataPrivate', 'options.metadata', 'options.metadataPrivate'])
            ->orderByPivot('order');
    }

    public function scopePublic(Builder $query): Builder
    {
        $salesChannel = Config::get('sales-channel.model');

        if ($salesChannel instanceof SalesChannel) {
            $query->whereHas('salesChannels', fn (Builder $subquery) => $subquery->where($salesChannel->getQualifiedKeyName(), $salesChannel->getKey())->where('availability_status', ProductSalesChannelStatus::PUBLIC->value));
        }

        return $query;
    }

    public function getPublicAttribute(): bool
    {
        return $this->isPublicForSalesChannel();
    }

    public function isPublicForSalesChannel(SalesChannel|string|null $salesChannel = null): bool
    {
        $salesChannel ??= Config::get('sales-channel.model');

        if (is_string($salesChannel)) {
            $salesChannel = SalesChannel::find($salesChannel);
        }

        if ($salesChannel instanceof SalesChannel) {
            $productSalesChannel = $this->salesChannels->where('id', $salesChannel->getKey())->first();

            return $productSalesChannel?->pivot->availability_status === ProductSalesChannelStatus::PUBLIC;
        }

        return false;
    }

    public function isHiddenForSalesChannel(?SalesChannel $salesChannel = null): bool
    {
        $salesChannel ??= Config::get('sales-channel.model');

        if ($salesChannel instanceof SalesChannel) {
            $productSalesChannel = $this->salesChannels->where('id', $salesChannel->getKey())->first();

            return $productSalesChannel?->pivot->availability_status === ProductSalesChannelStatus::HIDDEN;
        }

        return false;
    }

    public function attributes(): BelongsToMany
    {
        return $this->belongsToMany(Attribute::class, 'product_attribute')
            ->orderBy('order')
            ->withPivot('id')
            ->using(ProductAttribute::class);
    }

    public function sales(): BelongsToMany
    {
        return $this->belongsToMany(
            Discount::class,
            'product_sales',
            'product_id',
            'sale_id',
        );
    }

    public function pages(): BelongsToMany
    {
        return $this->belongsToMany(
            Page::class,
            'product_page',
        );
    }

    public function productSetSales(): Collection
    {
        $sales = Collection::make();
        $sets = $this->sets;

        /** @var ProductSet $set */
        foreach ($sets as $set) {
            $sales = $sales->merge($set->allProductsSales());
        }

        return $sales->unique('id');
    }

    public function allProductSales(Collection $salesWithBlockList): Collection
    {
        $sales = $this->discounts->filter(
            fn (Discount $discount): bool => $discount->code === null
                && $discount->active
                && $discount->target_type->is(DiscountTargetType::PRODUCTS)
                && $discount->target_is_allow_list
        );

        $salesBlockList = $salesWithBlockList->filter(function ($sale): bool {
            if ($sale->products->contains(fn ($value): bool => $value->getKey() === $this->getKey())) {
                return false;
            }
            foreach ($sale->productSets as $set) {
                if ($set->allProductsIds()->contains(fn ($value): bool => $value === $this->getKey())) {
                    return false;
                }
            }

            return true;
        });

        $sales = $sales->merge($salesBlockList);

        $productSetSales = $this->productSetSales();

        $sales = $sales->merge($productSetSales->where('target_is_allow_list', true));
        $sales = $sales->diff($productSetSales->where('target_is_allow_list', false));

        return $sales->unique('id');
    }

    private function prices(): MorphMany
    {
        return $this->morphMany(Price::class, 'model');
    }

    public function pricesForCurrentChannel(): MorphMany
    {
        $salesChannel = Config::get('sales-channel.model') ?? app(SalesChannelRepository::class)->getDefault();

        return $this->prices()->where('sales_channel_id', $salesChannel?->getKey());
    }

    public function pricesBase(): MorphMany
    {
        return $this->prices()->where('price_type', ProductPriceType::PRICE_BASE->value);
    }

    public function pricesBaseForCurrentChannel(): MorphMany
    {
        return $this->pricesForCurrentChannel()->where('price_type', ProductPriceType::PRICE_BASE->value);
    }

    public function pricesMin(): MorphMany
    {
        return $this->prices()->where('price_type', ProductPriceType::PRICE_MIN->value);
    }

    public function pricesMinForCurrentChannel(): MorphMany
    {
        return $this->pricesForCurrentChannel()->where('price_type', ProductPriceType::PRICE_MIN->value);
    }

    public function pricesMax(): MorphMany
    {
        return $this->prices()->where('price_type', ProductPriceType::PRICE_MAX->value);
    }

    public function pricesMaxForCurrentChannel(): MorphMany
    {
        return $this->pricesForCurrentChannel()->where('price_type', ProductPriceType::PRICE_MAX->value);
    }

    public function pricesMinInitial(): MorphMany
    {
        return $this->prices()->where('price_type', ProductPriceType::PRICE_MIN_INITIAL->value);
    }

    public function pricesMinInitialForCurrentChannel(): MorphMany
    {
        return $this->pricesForCurrentChannel()->where('price_type', ProductPriceType::PRICE_MIN_INITIAL->value);
    }

    public function pricesMaxInitial(): MorphMany
    {
        return $this->prices()->where('price_type', ProductPriceType::PRICE_MAX_INITIAL->value);
    }

    public function pricesMaxInitialForCurrentChannel(): MorphMany
    {
        return $this->pricesForCurrentChannel()->where('price_type', ProductPriceType::PRICE_MAX_INITIAL->value);
    }

    public function salesChannels(): BelongsToMany
    {
        return $this->belongsToMany(
            SalesChannel::class,
            (new ProductSalesChannel())->getTable(),
            'product_id',
            'sales_channel_id',
        )->using(ProductSalesChannel::class)
            ->withPivot(['availability_status']);
    }

    public function publicSalesChannels(): BelongsToMany
    {
        return $this->salesChannels()->wherePivot('availability_status', ProductSalesChannelStatus::PUBLIC->value);
    }
}
