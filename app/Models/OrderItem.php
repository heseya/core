<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @OA\Schema()
 */
class OrderItem extends Model
{
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

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class)->withTrashed();
    }

    /**
     * @OA\Property(
     *   property="schema_items",
     *   type="array",
     *   @OA\Items(ref="#/components/schemas/ProductSchemaItem"),
     * )
     */
    public function schemaItems()
    {
        return $this->belongsToMany(ProductSchemaItem::class)->withTrashed();
    }
}