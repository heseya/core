<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @OA\Schema ()
 *
 * @mixin IdeHelperDeposit
 */
class Deposit extends Model
{
    use HasFactory;

    /**
     * @OA\Property(
     *   property="id",
     *   type="string",
     *   example="026bc5f6-8373-4aeb-972e-e78d72a67121",
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
     *   type="string",
     *   example="026bc5f6-8373-4aeb-972e-e78d72a67121",
     * )
     *
     * @OA\Property(
     *   property="order_product_id",
     *   type="string",
     *   example="026bc5f6-8373-4aeb-972e-e78d72a67121",
     * )
     */
    protected $fillable = [
        'quantity',
        'item_id',
        'order_product_id',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function items(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }
}
