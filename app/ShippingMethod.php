<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

/**
 * @OA\Schema()
 */
class ShippingMethod extends Model
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
     *   example="Next Day Courier",
     * )
     *
     * @OA\Property(
     *   property="price",
     *   type="float",
     *   example=10.99,
     * )
     *
     * @OA\Property(
     *   property="public",
     *   type="boolean",
     * )
     */

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'price',
        'public',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'price' => 'float',
        'public' => 'boolean',
    ];
}
