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
     *   property="symbol",
     *   type="string",
     * )
     *
     * @OA\Property(
     *   property="qty",
     *   type="number",
     *   example=20,
     * )
     */

    protected $fillable = [
        'name',
        'symbol',
        'qty',
        'category_id',
    ];

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function photo()
    {
        return $this->belongsTo(Photo::class);
    }

    public function schemaItems()
    {
        return $this->hasMany(ProductSchemaItem::class);
    }
}
