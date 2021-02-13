<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @OA\Schema()
 */
class Item extends Model
{
    use SoftDeletes, HasFactory;

    /**
     * @OA\Property(
     *   property="id",
     *   type="string",
     *   example="026bc5f6-8373-4aeb-972e-e78d72a67121",
     * )
     *
     * @OA\Property(
     *   property="name",
     *   type="string",
     *   example="Chain",
     * )
     *
     * @OA\Property(
     *   property="sku",
     *   type="string",
     * )
     */

    protected $fillable = [
        'name',
        'sku',
    ];

    public function getQuantityAttribute (): float
    {
        return 100; // temp

//        $deposits = $this->deposits()->sum('quantity');
//        $withdrawals = $this->schemaItems()
//            ->join(
//                'order_item_product_schema_item',
//                'product_schema_items.id',
//                '=',
//                'order_item_product_schema_item.product_schema_item_id'
//            )->join(
//                'order_items',
//                'order_items.id',
//                '=',
//                'order_item_product_schema_item.order_item_id'
//            )->sum('quantity');
//
//        return $deposits - $withdrawals;
    }

    public function deposits (): HasMany
    {
        return $this->hasMany(Deposit::class);
    }
}
