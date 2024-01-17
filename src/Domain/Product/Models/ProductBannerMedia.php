<?php

declare(strict_types=1);

namespace Domain\Product\Models;

use App\Models\Interfaces\Translatable;
use App\Models\Media;
use App\Models\MediaBannerMedia;
use App\Models\Model;
use App\Models\Product;
use App\Traits\CustomHasTranslations;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * @mixin IdeHelperProductBannerMedia
 */
final class ProductBannerMedia extends Model implements Translatable
{
    use CustomHasTranslations;
    use HasFactory;

    public const HIDDEN_PERMISSION = 'products.show_hidden';

    protected $fillable = [
        'title',
        'subtitle',
        'url',
    ];

    /** @var string[] */
    protected array $translatable = [
        'title',
        'subtitle',
    ];

    /**
     * @return BelongsToMany<Media>
     */
    public function media(): BelongsToMany
    {
        return $this
            ->belongsToMany(Media::class, 'product_banner_responsive_media')
            ->with('metadata', 'metadataPrivate')
            ->withPivot('min_screen_width')
            ->using(MediaBannerMedia::class);
    }

    /**
     * @return HasOne<Product>
     */
    public function product(): HasOne
    {
        return $this->hasOne(Product::class, 'banner_media_id');
    }
}
