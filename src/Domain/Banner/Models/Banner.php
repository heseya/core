<?php

declare(strict_types=1);

namespace Domain\Banner\Models;

use App\Criteria\MetadataPrivateSearch;
use App\Criteria\MetadataSearch;
use App\Criteria\WhereInIds;
use App\Models\Model;
use App\Traits\HasMetadata;
use Heseya\Searchable\Criteria\Like;
use Heseya\Searchable\Traits\HasCriteria;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @mixin IdeHelperBanner
 */
final class Banner extends Model
{
    use HasCriteria;
    use HasFactory;
    use HasMetadata;

    protected $fillable = [
        'slug',
        'name',
        'active',
    ];

    protected $casts = [
        'active' => 'boolean',
    ];

    /** @var string[] */
    protected array $criteria = [
        'slug' => Like::class,
        'metadata' => MetadataSearch::class,
        'metadata_private' => MetadataPrivateSearch::class,
        'ids' => WhereInIds::class,
    ];

    /**
     * @return HasMany<BannerMedia>
     */
    public function bannerMedia(): HasMany
    {
        return $this->hasMany(BannerMedia::class, 'banner_id')
            ->orderBy('order');
    }
}
