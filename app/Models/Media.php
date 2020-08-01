<?php

namespace App\Models;

/**
 * @OA\Schema()
 */
class Media extends Model
{
    public const OTHER = 0;
    public const PHOTO = 1;
    public const VIDEO = 2;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'media';

    /**
     * @OA\Property(
     *   property="id",
     *   type="integer",
     * )
     *
     * @OA\Property(
     *   property="type",
     *   type="string",
     *   example="photo",
     * )
     *
     * @OA\Property(
     *   property="url",
     *   type="string",
     *   example="https://cdn.heseya.com/image.jpg"
     * )
     */

    protected $fillable = [
        'type',
        'url',
    ];

    public function products()
    {
        return $this->belongsToMany(Product::class, 'product_media');
    }
}
