<?php

namespace App\Models;

use App\SearchTypes\ProductSetSearch;
use Heseya\Searchable\Searches\Like;
use Heseya\Searchable\Traits\Searchable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

/**
 * @mixin IdeHelperProductSet
 */
class ProductSet extends Model
{
    use Searchable, HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'public',
        'order',
        'hide_on_index',
        'parent_id',
    ];

    protected $casts = [
        'public' => 'boolean',
        'hide_on_index' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected array $searchable = [
        'name' => Like::class,
        'slug' => Like::class,
        'search' => ProductSetSearch::class,
        'public',
    ];

    public function getSlugOverrideAttribute(): bool
    {
        return $this->parent ? !Str::startsWith(
            $this->slug, $this->parent->slug . '-',
        ) : false;
    }

    public function scopePublic($query)
    {
        return $query->where('public', true)
            ->where(function (Builder $query) {
                $query->whereHas('parent', function (Builder $query) {
                    $query->where('public', true);
                })->orWhereNull('parent_id');
            });
    }

    public function scopeRoot($query)
    {
        return $query->whereNull('parent_id');
    }

    public function scopeReversed($query)
    {
        return $query->withoutGlobalScope('ordered')
            ->orderBy('order', 'desc');
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        $query = $this->hasMany(self::class, 'parent_id');

        return $query;
    }

    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'product_set_product');
    }

    protected static function booted()
    {
        static::addGlobalScope(
            'ordered',
            fn (Builder $builder) => $builder->orderBy('order'),
        );
    }
}
