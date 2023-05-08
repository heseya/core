<?php

namespace App\Models;

use App\Criteria\MediaWhereHasRelations;
use App\Criteria\WhereInIds;
use App\Enums\MediaSource;
use App\Enums\MediaType;
use App\Traits\HasMetadata;
use Heseya\Searchable\Traits\HasCriteria;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * @mixin IdeHelperMedia
 */
class Media extends Model
{
    use HasFactory;
    use HasCriteria;
    use HasMetadata;

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
        'source',
    ];

    protected $casts = [
        'type' => MediaType::class,
        'source' => MediaSource::class,
    ];

    protected array $criteria = [
        'type',
        'source',
        'has_relationships' => MediaWhereHasRelations::class,
        'ids' => WhereInIds::class,
    ];

    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'product_media');
    }

    public function documents(): BelongsToMany
    {
        return $this
            ->belongsToMany(Media::class, 'order_document')
            ->using(OrderDocument::class)
            ->withPivot('id', 'type', 'name');
    }

    public function bannerMedia(): BelongsToMany
    {
        return $this
            ->belongsToMany(BannerMedia::class, 'media_responsive_media')
            ->withPivot('min_screen_width')
            ->using(MediaBannerMedia::class);
    }

    public function relationsCount(): int
    {
        return $this->products()->count()
            + $this->documents()->count()
            + $this->bannerMedia()->count();
    }
}
