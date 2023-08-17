<?php

declare(strict_types=1);

namespace Domain\ProductAttribute\Models;

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
use Domain\ProductAttribute\Enums\AttributeType;
use Domain\ProductSet\ProductSet;
use Heseya\Searchable\Criteria\Like;
use Heseya\Searchable\Traits\HasCriteria;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Translatable\HasTranslations;

/**
 * @property mixed $pivot
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

    protected const HIDDEN_PERMISSION = 'attributes.show_hidden';

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
    ];

    /** @var string[] */
    protected array $translatable = [
        'name',
    ];

    protected $casts = [
        'type' => AttributeType::class,
        'global' => 'boolean',
        'sortable' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'published' => 'array',
    ];

    /** @var string[] */
    protected array $criteria = [
        'global',
        'metadata' => MetadataSearch::class,
        'metadata_private' => MetadataPrivateSearch::class,
        'search' => AttributeSearch::class,
        'ids' => WhereInIds::class,
        'published' => Like::class,
        'attribute.published' => Like::class,
    ];

    /**
     * @return HasMany<AttributeOption>
     */
    public function options(): HasMany
    {
        return $this
            ->hasMany(AttributeOption::class)
            ->orderBy('order')
            ->with('metadata', 'metadataPrivate');
    }

    /**
     * @return BelongsToMany<Product>
     */
    public function products(): BelongsToMany
    {
        return $this
            ->belongsToMany(Product::class, 'product_attribute')
            ->withPivot('id')
            ->using(ProductAttribute::class);
    }

    /**
     * @return BelongsToMany<ProductSet>
     */
    public function productSets(): BelongsToMany
    {
        return $this->belongsToMany(ProductSet::class);
    }
}
