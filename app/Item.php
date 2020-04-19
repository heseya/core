<?php

namespace App;

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

    public function schemaItems()
    {
        return $this->hasMany(ProductSchemaItem::class);
    }
}
