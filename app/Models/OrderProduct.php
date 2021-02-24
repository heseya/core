<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @OA\Schema()
 */
class OrderProduct extends Model
{
    use HasFactory;

    /**
     * @OA\Property(
     *   property="product_id",
     *   type="string",
     *   example="026bc5f6-8373-4aeb-972e-e78d72a67121",
     * ),
     * @OA\Property(
     *   property="quantity",
     *   type="number",
     *   example="12.4",
     * ),
     * @OA\Property(
     *   property="price",
     *   type="number",
     *   example="199.99",
     * ),
     * @OA\Property(
     *   property="schemas",
     *   type="array",
     *   @OA\Items(ref="#/components/schemas/OrderSchema")
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

    public function schemas(): HasMany
    {
        return $this->hasMany(OrderSchema::class);
    }
}
