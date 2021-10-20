<?php

namespace App\Models;

use App\Enums\TwitterCardType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasOne;

class SeoMetadata extends Model
{
    use HasFactory;

    protected $fillable = [
        'global',
        'title',
        'description',
        'keywords',
        'og_image',
        'twitter_card',
    ];

    protected $casts = [
        'global' => 'bool',
        'keywords' => 'array',
        'twitter_card' => TwitterCardType::class,
    ];

    protected $attributes = [
        'global' => false,
    ];

    public function media(): HasOne
    {
        return $this->hasOne(Media::class, 'og_image', 'id');
    }

    public function getKeywordsAttribute($value)
    {
        return json_decode($value);
    }

    public function setKeywordsAttribute($value)
    {
        $this->attributes['keywords'] = json_encode($value);
    }
}
