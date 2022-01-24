<?php

namespace App\Models;

use App\Enums\TwitterCardType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Translatable\HasTranslations;

/**
 * @mixin IdeHelperSeoMetadata
 */
class SeoMetadata extends Model
{
    use HasFactory, SoftDeletes, HasTranslations;

    protected $fillable = [
        'global',
        'title',
        'description',
        'keywords',
        'og_image',
        'twitter_card',
        'model_id',
        'model_type',
        'no_index',
        'published',
    ];

    protected $translatable = [
        'title',
        'description',
        'keywords',
        'no_index',
    ];

    protected $casts = [
        'global' => 'bool',
        'keywords' => 'array',
        'twitter_card' => TwitterCardType::class,
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'no_index' => 'bool',
        'published' => 'array',
    ];

    protected $attributes = [
        'global' => false,
    ];

    public function getPublishedAttribute($value): array
    {
        return json_decode($value, true) ?? [];
    }

    public function media(): HasOne
    {
        return $this->hasOne(Media::class, 'id', 'og_image');
    }

    public function modelSeo(): MorphTo
    {
        return $this->morphTo('seo', 'model_type', 'model_id', 'id');
    }
}
