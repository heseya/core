<?php

namespace App\Models;

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
     *   property="description_md",
     *   type="string",
     *   description="Description in MD.",
     *   example="# Awesome stuff!",
     * )
     *
     * @OA\Property(
     *   property="public",
     *   type="boolean",
     * )
     *
     * @OA\Property(
     *   property="visible",
     *   type="boolean",
     * )
     */

    protected $fillable = [
        'name',
        'slug',
        'price',
        'description_md',
        'digital',
        'public',
        'brand_id',
        'category_id',
        'original_id',
        'user_id',
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
     * Description in HTML.
     *
     * @OA\Property(
     *   property="description_html",
     *   type="string",
     *   example="<h1>Awesome stuff!</h1>",
     * )
     *
     * @var string
     */
    public function getDescriptionHtmlAttribute(): string
    {
        return parsedown(strip_tags($this->description_md));
    }

    /**
     * Whether product is available.
     *
     * @OA\Property(
     *   property="available",
     *   type="boolean",
     * )
     *
     * @var bool
     */
    public function getAvailableAttribute(): bool
    {
        return $this->schemas()->exists() ? $this->schemas()->first()
            ->schemaItems()->first()->item->quantity > 0 : false;
    }

    /**
     * @return bool
     */
    public function isPublic(): bool
    {
        return $this->public && $this->brand->public && $this->category->public;
    }
}
