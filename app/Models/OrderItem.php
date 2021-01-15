<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * @OA\Schema()
 */
class OrderItem extends Model
{
    use HasFactory;

    /**
     * @OA\Property(
     *   property="product_id",
     *   type="integer",
     * )
     *
     * @OA\Property(
     *   property="quantity",
     *   type="number",
     *   example="12.34",
     * )
     */

    protected $fillable = [
        'quantity',
        'price',
        'order_id',
        'product_id',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class)->withTrashed();
    }
}
