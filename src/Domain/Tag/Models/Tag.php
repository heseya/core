<?php

declare(strict_types=1);

namespace Domain\Tag\Models;

use App\Criteria\TagSearch;
use App\Criteria\WhereInIds;
use App\Models\IdeHelperTag;
use App\Models\Interfaces\Translatable;
use App\Models\Model;
use App\Models\Product;
use App\Traits\CustomHasTranslations;
use Heseya\Searchable\Criteria\Like;
use Heseya\Searchable\Traits\HasCriteria;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * @mixin IdeHelperTag
 */
final class Tag extends Model implements Translatable
{
    use CustomHasTranslations;
    use HasCriteria;
    use HasFactory;

    protected const HIDDEN_PERMISSION = 'tags.show_hidden';

    protected $fillable = [
        'id',
        'name',
        'color',
        'published',
    ];

    /** @var string[] */
    protected array $translatable = [
        'name',
    ];

    /** @var array<string, class-string> */
    protected array $criteria = [
        'name' => Like::class,
        'color' => Like::class,
        'search' => TagSearch::class,
        'ids' => WhereInIds::class,
        'published' => Like::class,
        'tags.published' => Like::class,
    ];

    protected $casts = [
        'published' => 'array',
    ];

    /**
     * @return BelongsToMany<Product>
     */
    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'product_tags');
    }
}
