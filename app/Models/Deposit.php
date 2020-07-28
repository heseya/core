<?php

namespace App\Models;

/**
 * @OA\Schema()
 */
class Deposit extends Model
{
    /**
     * @OA\Property(
     *   property="id",
     *   type="integer",
     * )
     *
     * @OA\Property(
     *   property="quantity",
     *   type="float",
     *   example="12.5",
     * )
     *
     * @OA\Property(
     *   property="item_id",
     *   type="integer",
     * )
     */

    protected $fillable = [
        'quantity'
    ];

    public function items()
    {
        return $this->belongsTo(Item::class);
    }
}
