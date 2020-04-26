<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @OA\Schema()
 */
class Product extends Model
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
     *   example="Snake Ring",
     * )
     *
     * @OA\Property(
     *   property="slug",
     *   type="string",
     *   example="snake-ring",
     * )
     *
     * @OA\Property(
     *   property="price",
     *   type="number",
     *   example=229.99,
     * )
     *
     * @OA\Property(
     *   property="description",
     *   type="string",
     *   description="Description in HTML.",
     *   example="<p>Awesome stuff!</p>",
     * )
     *
     * @OA\Property(
     *   property="public",
     *   type="boolean",
     * )
     */

    protected $fillable = [
        'name',
        'slug',
        'price',
        'description',
        'digital',
        'public',
        'brand_id',
        'category_id',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'price' => 'float',
        'public' => 'bool',
        'digital' => 'bool',
    ];

    public function media()
    {
        return $this->belongsToMany(Media::class, 'product_media');
    }

    /**
     * @OA\Property(
     *   property="brand",
     *   ref="#/components/schemas/Brand",
     * )
     */
    public function brand()
    {
        return $this->belongsTo(Brand::class);
    }

    /**
     * @OA\Property(
     *   property="category",
     *   ref="#/components/schemas/Category",
     * )
     */
    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * @OA\Property(
     *   property="schemas",
     *   type="array",
     *   @OA\Items(ref="#/components/schemas/ProductSchema"),
     * )
     */
    public function schemas()
    {
        return $this->hasMany(ProductSchema::class);
    }

    public function orders()
    {
        return $this->belongsToMany(Order::class)->using(OrderItem::class);
    }

    /**
     * MD description parser.
     *
     * @var array
     */
    public function getDescriptionAttribute($description): string
    {
        return parsedown($description);
    }

    /**
     * @return bool
     */
    public function isPublic(): bool
    {
        return $this->public && $this->brand->public && $this->category->public;
    }
}
