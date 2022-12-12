<?php

namespace App\Models;

use App\Criteria\LessOrEquals;
use App\Criteria\MetadataPrivateSearch;
use App\Criteria\MetadataSearch;
use App\Criteria\MoreOrEquals;
use App\Criteria\ProductAttributeSearch;
use App\Criteria\ProductNotAttributeSearch;
use App\Criteria\ProductSearch;
use App\Criteria\WhereHasId;
use App\Criteria\WhereHasPhoto;
use App\Criteria\WhereHasSlug;
use App\Criteria\WhereInIds;
use App\Criteria\WhereNotId;
use App\Criteria\WhereNotSlug;
use App\Enums\DiscountTargetType;
use App\Models\Contracts\SortableContract;
use App\Services\Contracts\ProductSearchServiceContract;
use App\Traits\HasDiscountConditions;
use App\Traits\HasDiscounts;
use App\Traits\HasMetadata;
use App\Traits\HasSeoMetadata;
use App\Traits\Sortable;
use Heseya\Searchable\Criteria\Equals;
use Heseya\Searchable\Criteria\Like;
use Heseya\Searchable\Traits\HasCriteria;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Collection;
use JeroenG\Explorer\Application\Explored;
use JeroenG\Explorer\Application\SearchableFields;
use JeroenG\Explorer\Domain\Analysis\Analysis;
use JeroenG\Explorer\Domain\Analysis\Analyzer\StandardAnalyzer;
use Laravel\Scout\Searchable;
use OwenIt\Auditing\Auditable;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;

/**
 * @property mixed $pivot
 *
 * @mixin IdeHelperProduct
 */
class Product extends Model implements AuditableContract, Explored, SortableContract, SearchableFields
{
    use HasFactory,
        SoftDeletes,
        Searchable,
        Sortable,
        Auditable,
        HasSeoMetadata,
        HasMetadata,
        HasCriteria,
        HasDiscountConditions,
        HasDiscounts;

    protected $fillable = [
        'name',
        'slug',
        'price',
        'description_html',
        'description_short',
        'public',
        'quantity_step',
        'google_product_category',
        'vat_rate',
        'price_min',
        'price_max',
        'available',
        'order',
        'price_min_initial',
        'price_max_initial',
        'shipping_time',
        'shipping_date',
        'has_schemas',
        'quantity',
    ];

    protected array $auditInclude = [
        'name',
        'slug',
        'description_html',
        'description_short',
        'public',
        'quantity_step',
        'price_min',
        'price_max',
        'available',
        'order',
    ];

    protected $dates = [
        'shipping_date',
    ];

    protected $casts = [
        'price' => 'float',
        'public' => 'bool',
        'available' => 'bool',
        'quantity_step' => 'float',
        'vat_rate' => 'float',
        'has_schemas' => 'bool',
        'quantity' => 'float',
    ];

    protected array $sortable = [
        'id',
        'price',
        'name',
        'created_at',
        'updated_at',
        'order',
        'public',
        'available',
        'price_min',
        'price_max',
        'attribute.*',
        'set.*',
    ];

    protected array $criteria = [
        'search' => ProductSearch::class,
        'ids' => WhereInIds::class,
        'slug' => Like::class,
        'name' => Like::class,
        'public' => Equals::class,
        'available' => Equals::class,
        'sets' => WhereHasSlug::class,
        'sets_not' => WhereNotSlug::class,
        'tags' => WhereHasId::class,
        'tags_not' => WhereNotId::class,
        'metadata' => MetadataSearch::class,
        'metadata_private' => MetadataPrivateSearch::class,
        'price_max' => LessOrEquals::class,
        'price_min' => MoreOrEquals::class,
        'attribute' => ProductAttributeSearch::class,
        'attribute_not' => ProductNotAttributeSearch::class,
        'has_cover' => WhereHasPhoto::class,
    ];

    protected string $defaultSortBy = 'products.order';
    protected string $defaultSortDirection = 'desc';

    public function mappableAs(): array
    {
        $searchService = app(ProductSearchServiceContract::class);
        return $searchService->mappableAs();
    }

    public function toSearchableArray(): array
    {
        $searchService = app(ProductSearchServiceContract::class);
        return $searchService->mapSearchableArray($this);
    }

    public function getSearchableFields(): array
    {
        $searchService = app(ProductSearchServiceContract::class);
        return $searchService->searchableFields();
    }

    public function indexSettings(): array
    {
        $analyzer = new StandardAnalyzer('morfologik');
        $analyzer->setFilters(['lowercase', 'morfologik_stem']);

        return (new Analysis())
            ->addAnalyzer($analyzer)
            ->build();
    }

    public function sets(): BelongsToMany
    {
        return $this
            ->belongsToMany(ProductSet::class, 'product_set_product')
            ->withPivot('order');
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

    public function requiredSchemas(): BelongsToMany
    {
        return $this->schemas()->where('required', true);
    }

    public function schemas(): BelongsToMany
    {
        return $this
            ->belongsToMany(Schema::class, 'product_schemas')
            ->with(['options', 'metadata', 'metadataPrivate'])
            ->orderByPivot('order');
    }

    public function scopePublic(Builder $query): Builder
    {
        return $query->where('public', true);
    }

    public function attributes(): BelongsToMany
    {
        return $this->belongsToMany(Attribute::class, 'product_attribute')
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
        $sales = $this->discounts->filter(function ($discount): bool {
            if (
                $discount->code === null
                && $discount->target_type->is(DiscountTargetType::PRODUCTS)
                && $discount->target_is_allow_list
            ) {
                if ($discount->products->contains(function ($value): bool {
                    return $value->getKey() === $this->getKey();
                })) {
                    return true;
                }
            }
            return false;
        });

        $salesBlockList = $salesWithBlockList->filter(function ($sale): bool {
            if ($sale->products->contains(function ($value): bool {
                return $value->getKey() === $this->getKey();
            })) {
                return false;
            }
            foreach ($sale->productSets as $set) {
                if ($set->products->contains(function ($value): bool {
                    return $value->getKey() === $this->getKey();
                })) {
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
}
