<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @OA\Schema()
 */
class ProductSchemaItem extends Model
{
    use SoftDeletes;

    /**
     * @OA\Property(
     *   property="id",
     *   type="integer",
     * )
     *
     * @OA\Property(
     *   property="extra_price",
     *   type="number",
     *   example=19.99,
     * )
     *
     * @OA\Property(
     *   property="value",
     *   type="string",
     * )
     */

    protected $fillable = [
        'extra_price',
        'value',
        'item_id',
        'product_schema_id',
    ];

    public function schema()
    {
        return $this->belongsTo(ProductSchema::class, 'product_schema_id')->withTrashed();
    }

    /**
     * @OA\Property(
     *   property="item",
     *   ref="#/components/schemas/Item",
     * )
     */
    public function item()
    {
        return $this->belongsTo(Item::class)->withTrashed();
    }

    public function orderItems()
    {
        return $this->belongsToMany(OrderItem::class);
    }
}
