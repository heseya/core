<?php

namespace App\Models;

use App\Criteria\LessOrEquals;
use App\Criteria\MetadataPrivateSearch;
use App\Criteria\MetadataSearch;
use App\Criteria\MoreOrEquals;
use App\Criteria\ProductSearch;
use App\Criteria\WhereHasId;
use App\Criteria\WhereHasSlug;
use App\Criteria\WhereInIds;
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
use JeroenG\Explorer\Application\Explored;
use Laravel\Scout\Searchable;
use OwenIt\Auditing\Auditable;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;

/**
 * @mixin IdeHelperProduct
 */
class Product extends Model implements AuditableContract, Explored, SortableContract
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
        'price_min',
        'price_max',
        'available',
        'order',
        'min_price_discounted',
        'max_price_discounted',
        'google_product_category',
    ];

    protected $auditInclude = [
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

    protected $casts = [
        'price' => 'float',
        'public' => 'bool',
        'available' => 'bool',
        'quantity_step' => 'float',
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
    ];

    protected array $criteria = [
        'search' => ProductSearch::class,
        'ids' => WhereInIds::class,
        'slug' => Like::class,
        'name' => Like::class,
        'public' => Equals::class,
        'available' => Equals::class,
        'sets' => WhereHasSlug::class,
        'tags' => WhereHasId::class,
        'metadata' => MetadataSearch::class,
        'metadata_private' => MetadataPrivateSearch::class,
        'price_max' => LessOrEquals::class,
        'price_min' => MoreOrEquals::class,
    ];

    protected string $defaultSortBy = 'order';

    protected string $defaultSortDirection = 'desc';

    private ProductSearchServiceContract $searchService;

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);

        $this->searchService = app(ProductSearchServiceContract::class);
    }

    public function toSearchableArray(): array
    {
        return $this->searchService->mapSearchableArray($this);
    }

    public function sets(): BelongsToMany
    {
        return $this->belongsToMany(ProductSet::class, 'product_set_product');
    }

    public function items(): BelongsToMany
    {
        return $this
            ->belongsToMany(Item::class, 'item_product')
            ->withPivot('quantity')
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
            ->orderByPivot('order');
    }

    public function scopePublic($query): Builder
    {
        return $query->where('public', true);
    }

    public function attributes(): BelongsToMany
    {
        return $this->belongsToMany(Attribute::class, 'product_attribute')
            ->withPivot('id')
            ->using(ProductAttribute::class);
    }

    public function mappableAs(): array
    {
        return [];
    }

    public function indexSettings(): array
    {
        return [
            'analysis' => [
                'analyzer' => [
                    'standard_lowercase' => [
                        'type' => 'custom',
                        'tokenizer' => 'whitespace',
                        'filter' => [
                            'lowercase',
                            'morfologik_stem',
                        ],
                    ],
                ],
            ],
        ];
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
}
