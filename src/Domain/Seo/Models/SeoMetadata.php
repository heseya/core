<?php

declare(strict_types=1);

namespace Domain\Seo\Models;

use App\Models\Interfaces\Translatable;
use App\Models\Media;
use App\Models\Model;
use Domain\Seo\Enums\TwitterCardType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Translatable\HasTranslations;

/**
 * @mixin IdeHelperSeoMetadata
 */
final class SeoMetadata extends Model implements Translatable
{
    use HasFactory;
    use HasTranslations;
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
        'published',
        'header_tags',
    ];

    /** @var string[] */
    protected array $translatable = [
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

    /**
     * @return HasOne<Media>
     */
    public function media(): HasOne
    {
        return $this->hasOne(Media::class, 'id', 'og_image');
    }

    public function hasPublishedColumn(): bool
    {
        return true;
    }
}
