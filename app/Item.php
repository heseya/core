<?php

namespace App;

use App\Deposit;
use App\Category;
use App\ProductSchema;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @OA\Schema()
 */
class Item extends Model
{
    use SoftDeletes;

    /**
     * @OA\Property(
     *   property="id",
     *   type="integer",
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
        $deposits = $this->deposits()->sum('quantity');
        $withdrawals = 0;
        foreach ($this->schemaItems()->with('orderItems')->get() as $schemaItem) {
            $withdrawals += $schemaItem->orderItems()->sum('quantity');
        }

        return $deposits - $withdrawals;
    }

    public function deposits()
    {
        return $this->hasMany(Deposit::class);
    }

    public function schemaItems()
    {
        return $this->hasMany(ProductSchemaItem::class);
    }
}
