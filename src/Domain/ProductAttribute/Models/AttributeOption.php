<?php

declare(strict_types=1);

namespace Domain\ProductAttribute\Models;

use App\Criteria\AttributeOptionSearch;
use App\Criteria\MetadataPrivateSearch;
use App\Criteria\MetadataSearch;
use App\Criteria\ProductSetAttributeOptionSearch;
use App\Criteria\WhereInIds;
use App\Models\Contracts\SortableContract;
use App\Models\Interfaces\Translatable;
use App\Models\Model;
use App\Models\ProductAttribute;
use App\SortColumnTypes\TranslatedColumn;
use App\Traits\CustomHasTranslations;
use App\Traits\HasMetadata;
use App\Traits\Sortable;
use Heseya\Searchable\Criteria\Like;
use Heseya\Searchable\Traits\HasCriteria;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property string $name
 * @property ProductAttributeOption|null $product_attribute_option_pivot
 *
 * @mixin IdeHelperAttributeOption
 */
final class AttributeOption extends Model implements SortableContract, Translatable
{
    use CustomHasTranslations;
    use HasCriteria;
    use HasFactory;
    use HasMetadata;
    use SoftDeletes;
    use Sortable;

    public const HIDDEN_PERMISSION = 'attributes.show_hidden';
    protected $fillable = [
        'id',
        'name',
        'index',
        'value_number',
        'value_date',
        'attribute_id',
        'order',
    ];
    /** @var string[] */
    protected array $translatable = [
        'name',
    ];
    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'value_number' => 'float',
    ];
    /** @var string[] */
    protected array $criteria = [
        'search' => AttributeOptionSearch::class,
        'metadata' => MetadataSearch::class,
        'metadata_private' => MetadataPrivateSearch::class,
        'name' => Like::class,
        'ids' => WhereInIds::class,
        'product_set_slug' => ProductSetAttributeOptionSearch::class,
    ];
    /** @var string[] */
    protected array $sortable = [
        'name' => TranslatedColumn::class,
    ];

    /**
     * @return BelongsTo<Attribute,self>
     */
    public function attribute(): BelongsTo
    {
        return $this->belongsTo(Attribute::class);
    }

    /**
     * @return BelongsToMany<ProductAttribute>
     */
    public function productAttributes(): BelongsToMany
    {
        return $this->belongsToMany(
            ProductAttribute::class,
            'product_attribute_attribute_option',
            'attribute_option_id',
            'product_attribute_id',
            'id',
            'pivot_id',
        )->using(ProductAttributeOption::class)
            ->as('product_attribute_option_pivot');
    }
}
