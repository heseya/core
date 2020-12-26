<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * @OA\Schema()
 */
class Deposit extends Model
{
    use HasFactory;

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
