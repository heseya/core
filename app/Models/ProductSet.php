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
        'public_parent',
        'order',
        'hide_on_index',
        'parent_id',
    ];

    protected $casts = [
        'public' => 'boolean',
        'public_parent' => 'boolean',
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
        return $this->parent()->exists() && !Str::startsWith(
            $this->slug,
            $this->parent->slug . '-',
        );
    }

    public function getSlugSuffixAttribute(): string
    {
        return $this->slugOverride || $this->parent()->doesntExist() ? $this->slug :
            Str::after($this->slug, $this->parent->slug . '-');
    }

    public function scopePublic($query)
    {
        return $query->where('public', true)->where('public_parent', true);
    }

    public function scopeRoot($query): Builder
    {
        return $query->whereNull('parent_id');
    }

    public function scopeReversed($query): Builder
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
        return $this->hasMany(self::class, 'parent_id');
    }

    public function childrenPublic(): HasMany
    {
        return $this->children()->public();
    }

    public function allChildren(): HasMany
    {
        return $this->children()->with('allChildren');
    }

    public function allChildrenPublic(): HasMany
    {
        return $this->childrenPublic()->with('allChildrenPublic');
    }

    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'product_set_product');
    }

    protected static function booted(): void
    {
        static::addGlobalScope(
            'ordered',
            fn (Builder $builder) => $builder->orderBy('order'),
        );
    }
}
