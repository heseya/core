<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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

    public function items(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }
}
