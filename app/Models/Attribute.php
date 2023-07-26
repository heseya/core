<?php

namespace App\Models;

use App\Criteria\AttributeSearch;
use App\Criteria\MetadataPrivateSearch;
use App\Criteria\MetadataSearch;
use App\Criteria\WhereInIds;
use App\Enums\AttributeType;
use App\Traits\HasMetadata;
use Heseya\Searchable\Traits\HasCriteria;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property mixed $pivot
 * @property string $name
 * @property string $description
 * @property AttributeType $type
 *
 * @mixin IdeHelperAttribute
 */
class Attribute extends Model
{
    use HasCriteria;
    use HasFactory;
    use HasMetadata;

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
    ];

    protected $casts = [
        'type' => AttributeType::class,
        'global' => 'boolean',
        'sortable' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected array $criteria = [
        'global',
        'metadata' => MetadataSearch::class,
        'metadata_private' => MetadataPrivateSearch::class,
        'search' => AttributeSearch::class,
        'ids' => WhereInIds::class,
    ];

    public function options(): HasMany
    {
        return $this
            ->hasMany(AttributeOption::class)
            ->orderBy('order')
            ->with('metadata', 'metadataPrivate');
    }

    public function products(): BelongsToMany
    {
        return $this
            ->belongsToMany(Product::class, 'product_attribute')
            ->withPivot('id')
            ->using(ProductAttribute::class);
    }

    public function productSets(): BelongsToMany
    {
        return $this->belongsToMany(ProductSet::class);
    }
}
