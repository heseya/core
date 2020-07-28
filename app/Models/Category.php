<?php

namespace App\Models;

/**
 * @OA\Schema()
 */
class Category extends Model
{
    /**
     * @OA\Property(
     *   property="id",
     *   type="integer",
     * )
     *
     * @OA\Property(
     *   property="name",
     *   type="string",
     *   example="Rings & Sets",
     * )
     *
     * @OA\Property(
     *   property="slug",
     *   type="string",
     *   example="rings-and-sets",
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
