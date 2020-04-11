<?php

namespace App;

use App\Product;
use App\ProductSchemaItem;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @OA\Schema()
 */
class ProductSchema extends Model
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
     *   property="type",
     *   type="integer",
     * )
     *
     * @OA\Property(
     *   property="required",
     *   type="boolean",
     * )
     */

    protected $fillable = [
        'name',
        'type',
        'required',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'required' => 'boolean',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * @OA\Property(
     *   property="schemaItems",
     *   type="array",
     *   @OA\Items(ref="#/components/schemas/ProductSchemaItem"),
     * )
     */
    public function schemaItems()
    {
        return $this->hasMany(ProductSchemaItem::class)->with('item');
    }
}
