<?php

namespace App\Models;

use App\Enums\MediaType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * @mixin IdeHelperMedia
 */
class Media extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'media';

    protected $fillable = [
        'type',
        'url',
    ];

    protected $casts = [
        'type' => MediaType::class,
    ];

    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'product_media');
    }
}
