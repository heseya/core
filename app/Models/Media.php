<?php

namespace App\Models;

use App\Enums\MediaType;
use App\Traits\HasMetadata;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * @mixin IdeHelperMedia
 */
class Media extends Model
{
    use HasFactory, HasMetadata;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'media';

    protected $fillable = [
        'type',
        'url',
        'slug',
        'alt',
    ];

    protected $casts = [
        'type' => MediaType::class,
    ];

    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'product_media');
    }
}
