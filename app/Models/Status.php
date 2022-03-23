<?php

namespace App\Models;

use App\SearchTypes\MetadataPrivateSearch;
use App\SearchTypes\MetadataSearch;
use App\Traits\HasMetadata;
use Heseya\Searchable\Traits\Searchable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use OwenIt\Auditing\Auditable;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;

/**
 * @mixin IdeHelperStatus
 */
class Status extends Model implements AuditableContract
{
    use HasFactory, Searchable, Auditable, HasMetadata;

    protected $fillable = [
        'name',
        'color',
        'cancel',
        'description',
        'order',
        'hidden',
        'no_notifications',
    ];

    protected $casts = [
        'cancel' => 'boolean',
        'hidden' => 'boolean',
        'no_notifications' => 'boolean',
    ];

    protected $attributes = [
        'hidden' => false,
        'no_notifications' => false,
    ];

    protected array $searchable = [
        'metadata' => MetadataSearch::class,
        'metadata_private' => MetadataPrivateSearch::class,
    ];

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }
}
