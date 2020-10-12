<?php

namespace App\Models;

use Heseya\Searchable\Searches\Like;
use Heseya\Searchable\Traits\Searchable;

/**
 * @OA\Schema()
 */
class Brand extends Model
{
    use Searchable;

    /**
     * @OA\Property(
     *   property="id",
     *   type="integer",
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
     */

    protected $fillable = [
        'name',
        'slug',
        'public',
    ];

    protected $searchable = [
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
    ];

    public function products()
    {
        return $this->hasMany(Product::class);
    }
}
