<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @OA\Schema()
 */
class ProductSchemaItem extends Model
{
    use SoftDeletes, HasFactory;

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
    ];

    public function schema(): BelongsTo
    {
        return $this->belongsTo(ProductSchema::class, 'product_schema_id')->withTrashed();
    }

    /**
     * @OA\Property(
     *   property="item",
     *   ref="#/components/schemas/Item",
     * )
     */
    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class)->withTrashed();
    }

    public function orderItems(): BelongsToMany
    {
        return $this->belongsToMany(OrderItem::class);
    }
}
