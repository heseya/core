<?php

namespace App\Models;

use App\SearchTypes\ProductSearch;
use App\SearchTypes\WhereBelongsToManyById;
use App\SearchTypes\WhereInIds;
use App\Traits\HasSeoMetadata;
use Heseya\Searchable\Searches\Like;
use Heseya\Searchable\Traits\Searchable;
use Heseya\Sortable\Sortable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Auditable;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;

/**
 * @mixin IdeHelperProduct
 */
class Product extends Model implements AuditableContract
{
    use HasFactory, SoftDeletes, Searchable, Sortable, Auditable, HasSeoMetadata;

    protected $fillable = [
        'name',
        'slug',
        'price',
        'description_html',
        'description_short',
        'public',
        'public_legacy',
        'quantity_step',
        'price_min',
        'price_max',
        'available',
        'order',
    ];

    protected $auditInclude = [
        'name',
        'slug',
        'price',
        'description_html',
        'description_short',
        'public',
        'quantity_step',
    ];

    protected $casts = [
        'price' => 'float',
        'public' => 'bool',
        'available' => 'bool',
        'quantity_step' => 'float',
    ];

    protected array $searchable = [
        'ids' => WhereInIds::class,
        'name' => Like::class,
        'slug' => Like::class,
        'public',
        'search' => ProductSearch::class,
        'tags' => WhereBelongsToManyById::class,
    ];

    protected array $sortable = [
        'id',
        'price',
        'name',
        'created_at',
        'updated_at',
        'order',
        'public',
    ];

    protected string $defaultSortBy = 'created_at';
    protected string $defaultSortDirection = 'desc';

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

    public function schemas(): BelongsToMany
    {
        return $this
            ->belongsToMany(Schema::class, 'product_schemas')
            ->orderByPivot('order');
    }

    public function requiredSchemas(): BelongsToMany
    {
        return $this->schemas()->where('required', true);
    }

    public function sets(): BelongsToMany
    {
        return $this->belongsToMany(ProductSet::class, 'product_set_product');
    }

    public function scopePublic($query): Builder
    {
        return $query->where('public', true);
    }
}
