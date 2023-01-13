<?php

namespace App\Models;

use App\Criteria\MetadataPrivateSearch;
use App\Criteria\MetadataSearch;
use App\Traits\HasMetadata;
use Heseya\Searchable\Criteria\Like;
use Heseya\Searchable\Traits\HasCriteria;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @mixin IdeHelperBanner
 */
class Banner extends Model
{
    use HasFactory,
        HasCriteria,
        HasMetadata;

    protected $fillable = [
        'slug',
        'name',
        'active',
    ];

    protected $casts = [
        'active' => 'boolean',
    ];

    protected array $criteria = [
        'slug' => Like::class,
        'metadata' => MetadataSearch::class,
        'metadata_private' => MetadataPrivateSearch::class,
    ];

    public function bannerMedia(): HasMany
    {
        return $this->hasMany(BannerMedia::class, 'banner_id')
            ->orderBy('order');
    }
}
