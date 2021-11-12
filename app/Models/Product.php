<?php

namespace App\Models;

use App\SearchTypes\ProductSearch;
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
        'public',
        'quantity_step',
    ];

    protected $casts = [
        'price' => 'float',
        'public' => 'bool',
        'available' => 'bool',
        'quantity_step' => 'float',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected array $searchable = [
        'name' => Like::class,
        'slug' => Like::class,
        'public',
        'search' => ProductSearch::class,
    ];

    protected array $sortable = [
        'id',
        'price',
        'name',
        'created_at',
        'updated_at',
        'order',
    ];

    protected string $defaultSortBy = 'created_at';
    protected string $defaultSortDirection = 'desc';

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

    public function getAvailableAttribute(): bool
    {
        if ($this->schemas->count() <= 0) {
            return true;
        }

        // a product is available if all required schematics are available
        foreach ($this->schemas as $schema) {
            if ($schema->required && !$schema->available) {
                return false;
            }
        }

        return true;
    }

    public function schemas(): BelongsToMany
    {
        return $this
            ->belongsToMany(Schema::class, 'product_schemas')
            ->orderByPivot('order');
    }

    public function isPublic(): bool
    {
        $isAnySetPublic = $this->sets->count() === 0 ||
            $this->sets->where('public', true)->where('public_parent', true);

        return $this->public && $isAnySetPublic;
    }

    public function sets(): BelongsToMany
    {
        return $this->belongsToMany(ProductSet::class, 'product_set_product');
    }

    public function scopePublic($query): Builder
    {
        $query->where('public', true)->where(function (Builder $query): void {
            $query
                ->whereDoesntHave('sets')
                ->orWhereHas(
                    'sets',
                    fn (Builder $builder) => $builder
                        ->where('public', true)->where('public_parent', true),
                );
        });

        return $query;
    }
}
