<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

/**
 * @OA\Schema()
 */
class Brand extends Model
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

    public function products()
    {
        return $this->hasMany(Product::class);
    }
}
