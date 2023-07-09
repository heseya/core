<?php

namespace App\Models;

use App\Enums\TwitterCardType;
use App\Models\Interfaces\Translatable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Translatable\HasTranslations;

/**
 * @mixin IdeHelperSeoMetadata
 */
class SeoMetadata extends Model implements Translatable
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
        'header_tags',
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
        'header_tags' => 'array',
    ];

    protected $attributes = [
        'global' => false,
    ];

    public function media(): HasOne
    {
        return $this->hasOne(Media::class, 'id', 'og_image');
    }

    public function getKeywordsAttribute(string|null $value): mixed
    {
        return $value ? json_decode($value) : null;
    }

    public function setKeywordsAttribute(mixed $value): void
    {
        $this->attributes['keywords'] = json_encode($value);
    }

    public function modelSeo(): MorphTo
    {
        return $this->morphTo('seo', 'model_type', 'model_id', 'id');
    }
}
