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
use App\SortColumnTypes\TranslatedRawColumn;
use App\Traits\CustomHasTranslations;
use App\Traits\HasDiscountConditions;
use App\Traits\HasDiscounts;
use App\Traits\HasMediaAttachments;
use App\Traits\HasMetadata;
use App\Traits\HasSeoMetadata;
use App\Traits\Sortable;
use Domain\Page\Page;
use Domain\Price\Enums\ProductPriceType;
use Domain\PriceMap\PriceMap;
use Domain\PriceMap\PriceMapProductPrice;
use Domain\Product\Models\ProductBannerMedia;
use Domain\ProductAttribute\Enums\AttributeType;
use Domain\ProductAttribute\Models\Attribute;
use Domain\ProductAttribute\Models\AttributeOption;
use Domain\ProductSchema\Models\Schema;
use Domain\ProductSet\ProductSet;
use Domain\SalesChannel\Models\SalesChannel;
use Domain\SalesChannel\SalesChannelService;
use Domain\Tag\Models\Tag;
use Heseya\Searchable\Criteria\Equals;
use Heseya\Searchable\Criteria\Like;
use Heseya\Searchable\Traits\HasCriteria;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Config;
use Laravel\Scout\Searchable;

/**
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
    use Searchable;
    use SoftDeletes;
    use Sortable;

    public const HIDDEN_PERMISSION = 'products.show_hidden';

    protected $fillable = [
        'id',
        'name',
        'searchable_name',
        'slug',
        'description_html',
        'description_short',
        'public',
        'quantity_step',
        'google_product_category',
        'available',
        'shipping_time',
        'shipping_date',
        'has_schemas',
        'quantity',
        'shipping_digital',
        'purchase_limit_per_user',
        'published',
        'search_values',
    ];

    protected array $translatable = [
        'name',
        'description_html',
        'description_short',
    ];

    protected $casts = [
        'shipping_date' => 'date',
        'public' => 'bool',
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
        'name' => TranslatedRawColumn::class,
        'created_at',
        'updated_at',
        'public',
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
        'public' => Equals::class,
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

    public function ancestorSets(): BelongsToMany
    {
        return $this
            ->belongsToMany(ProductSet::class, 'product_set_product_descendant')
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

    public function requiredSchemas(): HasMany
    {
        return $this->schemas()->where('required', true);
    }

    public function schemas(): HasMany
    {
        return $this
            ->hasMany(Schema::class)
            ->with(['options', 'metadata', 'metadataPrivate', 'options.metadata', 'options.metadataPrivate']);
    }

    /**
     * @deprecated
     */
    public function oldRequiredSchemas(): BelongsToMany
    {
        return $this->oldSchemas()->where('required', true);
    }

    /**
     * @deprecated
     */
    public function oldSchemas(): BelongsToMany
    {
        return $this
            ->belongsToMany(Schema::class, 'product_schemas')
            ->with(['options', 'metadata', 'metadataPrivate', 'options.metadata', 'options.metadataPrivate'])
            ->orderByPivot('order');
    }

    public function scopePublic(Builder $query): Builder
    {
        return $query->where('public', true);
    }

    public function attributes(): BelongsToMany
    {
        return $this->belongsToMany(Attribute::class, 'product_attribute')
            ->withPivot(['pivot_id'])
            ->using(ProductAttribute::class)
            ->as('product_attribute_pivot')
            ->orderBy('order', 'asc')
            ->orderBy('id', 'asc');
    }

    public function productAttributes(): HasMany
    {
        return $this->hasMany(ProductAttribute::class)
            ->leftJoin('attributes', 'attribute_id', '=', 'attributes.id')
            ->orderBy('attributes.order', 'asc');
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

    public function allProductSales(Collection $salesWithBlockList): Collection
    {
        $sales = $this->discounts->filter(
            fn (Discount $discount): bool => $discount->code === null
                && $discount->active
                && $discount->target_type->is(DiscountTargetType::PRODUCTS)
                && $discount->target_is_allow_list,
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

    /**
     * @deprecated
     */
    public function pricesBase(): MorphMany
    {
        return $this->prices()->where('price_type', ProductPriceType::PRICE_BASE->value);
    }

    /**
     * @return MorphMany<Price>
     */
    public function pricesMin(): MorphMany
    {
        return $this->prices()->where('price_type', ProductPriceType::PRICE_MIN->value);
    }

    /**
     * @return MorphMany<Price>
     */
    public function pricesMinInitial(): MorphMany
    {
        return $this->prices()->where('price_type', ProductPriceType::PRICE_MIN_INITIAL->value);
    }

    /**
     * @return MorphMany<Price>
     */
    private function prices(): MorphMany
    {
        return $this->morphMany(Price::class, 'model')
            ->whereNotNull('sales_channel_id');
    }

    public function getCachedInitialPriceForSalesChannel(SalesChannel|string|null $salesChannel = null): ?Price
    {
        if ($salesChannel === null) {
            $salesChannel = app(SalesChannelService::class)->getCurrentRequestSalesChannel();
        }

        return $this->relationLoaded('pricesMinInitial')
            ? $this->pricesMinInitial->where('sales_channel_id', $salesChannel instanceof SalesChannel ? $salesChannel->id : $salesChannel)->first()
            : $this->pricesMinInitial()->ofSalesChannel($salesChannel)->first();
    }

    public function getCachedMinPriceForSalesChannel(SalesChannel|string|null $salesChannel = null): ?Price
    {
        if ($salesChannel === null) {
            $salesChannel = app(SalesChannelService::class)->getCurrentRequestSalesChannel();
        }

        return $this->relationLoaded('pricesMin')
            ? $this->pricesMin->where('sales_channel_id', $salesChannel instanceof SalesChannel ? $salesChannel->id : $salesChannel)->first()
            : $this->pricesMin()->ofSalesChannel($salesChannel)->first();
    }

    /**
     * @return HasMany<PriceMapProductPrice>
     */
    public function mapPrices(): HasMany
    {
        return $this->hasMany(PriceMapProductPrice::class);
    }

    public function mappedPriceForPriceMap(PriceMap|string $priceMapId): PriceMapProductPrice
    {
        return $this->relationLoaded('mapPrices')
            ? $this->mapPrices->where('price_map_id', $priceMapId instanceof PriceMap ? $priceMapId->id : $priceMapId)->firstOrFail()
            : $this->mapPrices()->ofPriceMap($priceMapId)->firstOrFail();
    }

    protected function makeAllSearchableUsing(Builder $query): Builder
    {
        return $query->with('productAttributes');
    }

    public function toSearchableArray(): array
    {
        $attributes = [];
        /** @var ProductAttribute $productAttribute */
        foreach ($this->productAttributes as $productAttribute) {
            /** @var Attribute|null $attribute */
            $attribute = $productAttribute->attribute;
            if (!$attribute || !$attribute->include_in_text_search) {
                continue;
            }

            $values = [];
            /** @var AttributeOption $option */
            foreach ($productAttribute->options as $option) {
                $values[] = match ($attribute->type) {
                    // AttributeType::NUMBER => $option->value_number,
                    AttributeType::DATE => $option->value_date,
                    default => $option->name,
                };
            }

            $attributes['attribute_' . $attribute->slug] = implode(', ', $values);
        }

        return array_merge(
            [
                'id' => $this->id,
                'name' => $this->searchable_name,
            ],
            Config::get('search.search_in_descriptions')
                ? [
                    'description_html' => $this->description_html,
                    'description_short' => $this->description_short,
                    'search_values' => $this->search_values,
                ]
                : [],
            $attributes,
        );
    }

    /**
     * @return BelongsTo<ProductBannerMedia, self>
     */
    public function banner(): BelongsTo
    {
        return $this->belongsTo(ProductBannerMedia::class, 'banner_media_id');
    }

    protected static function booted(): void
    {
        static::saving(function (Product $product): void {
            $product->searchable_name = collect($product->getTranslations('name'))->values()->map(fn (string $translation) => trim($translation))->unique()->implode(' ');
        });
    }
}
