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
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'global',
        'title',
        'description',
        'keywords',
        'og_image',
        'twitter_card',
        'model_id',
        'model_type',
    ];

    protected $casts = [
        'global' => 'bool',
        'keywords' => 'array',
        'twitter_card' => TwitterCardType::class,
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected $attributes = [
        'global' => false,
    ];

    public function media(): HasOne
    {
        return $this->hasOne(Media::class, 'id', 'og_image');
    }

    public function getKeywordsAttribute($value)
    {
        return json_decode($value);
    }

    public function setKeywordsAttribute($value)
    {
        $this->attributes['keywords'] = json_encode($value);
    }

    public function modelSeo(): MorphTo
    {
        return $this->morphTo('seo', 'model_type', 'model_id', 'id');
    }
}
