<?php

namespace App\Models;

use App\Criteria\MetadataPrivateSearch;
use App\Criteria\MetadataSearch;
use App\Criteria\WhereInIds;
use App\Models\Interfaces\Translatable;
use App\Traits\CustomHasTranslations;
use App\Traits\HasMetadata;
use Heseya\Searchable\Criteria\Like;
use Heseya\Searchable\Traits\HasCriteria;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property string $name
 * @property string $description
 *
 * @mixin IdeHelperStatus
 */
class Status extends Model implements Translatable
{
    use CustomHasTranslations;
    use HasCriteria;
    use HasFactory;
    use HasMetadata;

    protected const HIDDEN_PERMISSION = 'statuses.show_hidden';

    protected $fillable = [
        'name',
        'color',
        'cancel',
        'description',
        'order',
        'hidden',
        'no_notifications',
        'published',
    ];

    protected $casts = [
        'cancel' => 'boolean',
        'hidden' => 'boolean',
        'no_notifications' => 'boolean',
        'published' => 'array',
    ];

    protected array $translatable = [
        'name',
        'description',
    ];

    protected $attributes = [
        'hidden' => false,
        'no_notifications' => false,
    ];

    protected array $criteria = [
        'metadata' => MetadataSearch::class,
        'metadata_private' => MetadataPrivateSearch::class,
        'ids' => WhereInIds::class,
        'published' => Like::class,
        'statuses.published' => Like::class,
    ];

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }
}
