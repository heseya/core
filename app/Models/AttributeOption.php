<?php

namespace App\Models;

use App\Criteria\AttributeOptionSearch;
use App\Criteria\MetadataPrivateSearch;
use App\Criteria\MetadataSearch;
use App\Criteria\WhereInIds;
use App\Traits\HasMetadata;
use Heseya\Searchable\Criteria\Like;
use Heseya\Searchable\Traits\HasCriteria;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property string $name
 *
 * @mixin IdeHelperAttributeOption
 */
class AttributeOption extends Model
{
    use HasCriteria;
    use HasFactory;
    use HasMetadata;
    use SoftDeletes;

    protected $fillable = [
        'id',
        'name',
        'index',
        'value_number',
        'value_date',
        'attribute_id',
        'order',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected array $criteria = [
        'search' => AttributeOptionSearch::class,
        'metadata' => MetadataSearch::class,
        'metadata_private' => MetadataPrivateSearch::class,
        'name' => Like::class,
        'ids' => WhereInIds::class,
    ];

    public function attribute(): BelongsTo
    {
        return $this->belongsTo(Attribute::class);
    }
}
