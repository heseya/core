<?php

namespace App\Models;

use Heseya\Searchable\Searches\Like;
use Heseya\Searchable\Traits\Searchable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @OA\Schema()
 */
class Brand extends Model
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
     *   example="Depth Steel",
     * )
     *
     * @OA\Property(
     *   property="slug",
     *   type="string",
     *   example="depth-steel",
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
    ];

    protected array $searchable = [
        'name' => Like::class,
        'slug' => Like::class,
        'public',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'public' => 'boolean',
        'hide_on_index' => 'boolean',
    ];

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }
}
