<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

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

    public function products()
    {
        return $this->hasMany(Product::class);
    }
}
