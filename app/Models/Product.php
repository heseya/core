<?php

namespace App\Models;

use App\SearchTypes\ProductSearch;
use App\Traits\Sortable;
use Heseya\Searchable\Searches\Like;
use Heseya\Searchable\Traits\Searchable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @mixin IdeHelperProduct
 */
class Product extends Model
{
    use HasFactory, SoftDeletes, Searchable, Sortable;

    protected $fillable = [
        'name',
        'slug',
        'price',
        'description_md',
        'public',
        'brand_id',
        'category_id',
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

    public function sets(): BelongsToMany
    {
        return $this->belongsToMany(ProductSet::class, 'product_set_product');
    }

    public function brand(): BelongsTo
    {
        return $this->belongsTo(ProductSet::class, 'brand_id');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(ProductSet::class, 'category_id');
    }

    public function schemas(): BelongsToMany
    {
        return $this
            ->belongsToMany(Schema::class, 'product_schemas')
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

    public function getDescriptionHtmlAttribute(): string
    {
        return parsedown(strip_tags($this->description_md));
    }

    public function getAvailableAttribute(): bool
    {
        if ($this->schemas()->count() <= 0) {
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

    public function isPublic(): bool
    {
        $isBrandPublic = !$this->brand || $this->brand->public && $this->brand->public_parent;

        $isCategoryPublic = !$this->category || $this->category->public && $this->category->public_parent;

        $isAnySetPublic = !($this->sets()->count() > 0) ||
            $this->sets()->where('public', true)->where('public_parent', true);

        return $this->public && $isBrandPublic && $isCategoryPublic && $isAnySetPublic;
    }

    public function scopePublic($query): Builder
    {
        $query->where('public', true);

        $query->where('public', true)
            ->where(function (Builder $query): void {
                $query
                    ->whereDoesntHave('brand')
                    ->orWhereHas(
                        'brand',
                        fn (Builder $builder) => $builder
                            ->where('public', true)->where('public_parent', true),
                    );
            })
            ->where(function (Builder $query): void {
                $query
                    ->whereDoesntHave('category')
                    ->orWhereHas(
                        'category',
                        fn (Builder $builder) => $builder
                            ->where('public', true)->where('public_parent', true),
                    );
            })
            ->where(function (Builder $query): void {
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
