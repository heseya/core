<?php

declare(strict_types=1);

namespace Domain\Page;

use App\Criteria\MetadataPrivateSearch;
use App\Criteria\MetadataSearch;
use App\Criteria\PageSearch;
use App\Criteria\WhereInIds;
use App\Models\Contracts\SeoContract;
use App\Models\Contracts\SortableContract;
use App\Models\Interfaces\Translatable;
use App\Models\Model;
use App\Models\Product;
use App\Traits\CustomHasTranslations;
use App\Traits\HasMetadata;
use App\Traits\HasSeoMetadata;
use App\Traits\Sortable;
use Heseya\Searchable\Criteria\Like;
use Heseya\Searchable\Traits\HasCriteria;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @mixin IdeHelperPage
 */
final class Page extends Model implements SeoContract, SortableContract, Translatable
{
    use CustomHasTranslations;
    use HasCriteria;
    use HasFactory;
    use HasMetadata;
    use HasSeoMetadata;
    use SoftDeletes;
    use Sortable;

    public const HIDDEN_PERMISSION = 'pages.show_hidden';

    protected $fillable = [
        'order',
        'name',
        'slug',
        'public',
        'content_html',
        'published',
    ];

    /** @var string[] */
    protected array $translatable = [
        'name',
        'content_html',
    ];

    protected $casts = [
        'public' => 'boolean',
        'published' => 'array',
    ];

    /** @var string[] */
    protected array $sortable = [
        'order',
        'created_at',
        'updated_at',
    ];

    /** @var array<string, class-string> */
    protected array $criteria = [
        'search' => PageSearch::class,
        'metadata' => MetadataSearch::class,
        'metadata_private' => MetadataPrivateSearch::class,
        'ids' => WhereInIds::class,
        'published' => Like::class,
        'pages.published' => Like::class,
    ];

    /**
     * @return BelongsToMany<Product>
     */
    public function products(): BelongsToMany
    {
        return $this->belongsToMany(
            Product::class,
            'product_page',
        );
    }
}
