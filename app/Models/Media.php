<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * @OA\Schema ()
 * @mixin IdeHelperMedia
 */
class Media extends Model
{
    use HasFactory;

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
     *   type="string",
     *   example="026bc5f6-8373-4aeb-972e-e78d72a67121",
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

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'product_media');
    }
}
