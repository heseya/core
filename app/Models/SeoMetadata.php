<?php

namespace App\Models;

use App\Enums\TwitterCardType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @mixin IdeHelperSeoMetadata
 */
class SeoMetadata extends Model
{
    use HasFactory;
    use SoftDeletes;

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
        'header_tags',
    ];

    protected $casts = [
        'global' => 'bool',
        'keywords' => 'array',
        'twitter_card' => TwitterCardType::class,
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'no_index' => 'bool',
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
