<?php

namespace App\Models;

use App\Models\Interfaces\Translatable;
use App\Criteria\MetadataPrivateSearch;
use App\Criteria\MetadataSearch;
use App\Criteria\WhereInIds;
use App\Traits\HasMetadata;
use Heseya\Searchable\Traits\HasCriteria;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use OwenIt\Auditing\Auditable;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;
use Spatie\Translatable\HasTranslations;

/**
 * @mixin IdeHelperStatus
 */
class Status extends Model implements AuditableContract, Translatable
{
    use HasFactory, Auditable, HasTranslations;
    use HasMetadata;
    use HasCriteria;

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

    protected $translatable = [
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
    ];

    public function getPublishedAttribute($value): array
    {
        return json_decode($value, true) ?? [];
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }
}
