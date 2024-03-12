<?php

declare(strict_types=1);

namespace Domain\ProductAttribute\Models;

use App\Criteria\AttributeProductSetsCriteria;
use App\Criteria\AttributeSearch;
use App\Criteria\MetadataPrivateSearch;
use App\Criteria\MetadataSearch;
use App\Criteria\WhereInIds;
use App\Models\Interfaces\Translatable;
use App\Models\Model;
use App\Models\Product;
use App\Models\ProductAttribute;
use App\Traits\CustomHasTranslations;
use App\Traits\HasMetadata;
use App\Traits\IsReorderable;
use Domain\ProductAttribute\Enums\AttributeType;
use Domain\ProductSet\ProductSet;
use Heseya\Searchable\Criteria\Like;
use Heseya\Searchable\Traits\HasCriteria;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property mixed $pivot
 * @property ProductAttribute|null $product_attribute_pivot
 * @property string $name
 * @property string $description
 * @property AttributeType $type
 *
 * @mixin IdeHelperAttribute
 */
final class Attribute extends Model implements Translatable
{
    use CustomHasTranslations;
    use HasCriteria;
    use HasFactory;
    use HasMetadata;
    use IsReorderable;

    public const HIDDEN_PERMISSION = 'attributes.show_hidden';

    protected $fillable = [
        'id',
        'name',
        'slug',
        'description',
        'min_number',
        'max_number',
        'min_date',
        'max_date',
        'type',
        'global',
        'sortable',
        'order',
        'published',
        'include_in_text_search',
    ];

    /** @var string[] */
    protected array $translatable = [
        'name',
        'description',
    ];

    protected $casts = [
        'type' => AttributeType::class,
        'global' => 'boolean',
        'sortable' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'published' => 'array',
        'include_in_text_search' => 'boolean',
    ];

    /** @var string[] */
    protected array $criteria = [
        'global',
        'metadata' => MetadataSearch::class,
        'metadata_private' => MetadataPrivateSearch::class,
        'search' => AttributeSearch::class,
        'ids' => WhereInIds::class,
        'published' => Like::class,
        'attributes.published' => Like::class,
        'sortable',
        'sets' => AttributeProductSetsCriteria::class,
    ];

    /**
     * @return HasMany<AttributeOption>
     */
    public function options(): HasMany
    {
        return $this
            ->hasMany(AttributeOption::class)
            ->orderBy('order', 'asc')
            ->with('metadata', 'metadataPrivate');
    }

    /**
     * @return BelongsToMany<Product>
     */
    public function products(): BelongsToMany
    {
        return $this
            ->belongsToMany(Product::class, 'product_attribute')
            ->using(ProductAttribute::class)
            ->as('product_attribute_pivot')
            ->withPivot(['pivot_id']);
    }

    /**
     * @return BelongsToMany<ProductSet>
     */
    public function productSets(): BelongsToMany
    {
        return $this->belongsToMany(ProductSet::class)->withPivot('order');
    }

    /**
     * @param Builder<self> $query
     */
    public function scopeTextSearchable(Builder $query): void
    {
        $query->where('include_in_text_search', '=', true);
    }
}
