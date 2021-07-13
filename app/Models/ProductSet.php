<?php

namespace App\Models;

use Heseya\Searchable\Searches\Like;
use Heseya\Searchable\Traits\Searchable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @OA\Schema ()
 *
 * @mixin IdeHelperProductSet
 */
class ProductSet extends Model
{
    use Searchable, HasFactory;

    /**
     * @OA\Property(
     *   property="id",
     *   type="string",
     *   example="026bc5f6-8373-4aeb-972e-e78d72a67121",
     * )
     *
     * @OA\Property(
     *   property="name",
     *   type="string",
     *   example="Rings",
     * )
     *
     * @OA\Property(
     *   property="slug",
     *   type="string",
     *   example="rings",
     * )
     *
     * @OA\Property(
     *   property="public",
     *   type="boolean",
     * )
     *
     * @OA\Property(
     *   property="hide_on_index",
     *   type="boolean",
     * )
     */

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
        'parent_id',
        'public',
        'hide_on_index',
    ];

    public function scopePrivate($query)
    {
        return $query->withoutGlobalScope('public');
    }

    public function scopeSubset($query)
    {
        return $query->withoutGlobalScope('global_set');
    }

    public function scopeEverything($query)
    {
        return $query->private()->subset();
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class);
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class);
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    protected static function booted()
    {
        static::addGlobalScope(
            'public',
            fn (Builder $builder) => $builder->where('public', false),
        );

        static::addGlobalScope(
            'global_set',
            fn (Builder $builder) => $builder->whereNull('parent_id'),
        );
    }
}
