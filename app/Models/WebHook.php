<?php

namespace App\Models;

use App\Models\Contracts\SortableContract;
use App\Traits\Sortable;
use Heseya\Searchable\Criteria\Like;
use Heseya\Searchable\Traits\HasCriteria;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Notifications\Notifiable;
use OwenIt\Auditing\Auditable;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;

/**
 * @mixin IdeHelperWebHook
 */
class WebHook extends Model implements AuditableContract, SortableContract
{
    use HasFactory, SoftDeletes, HasCriteria, Sortable, Auditable, Notifiable;

    protected $fillable = [
        'name',
        'url',
        'secret',
        'events',
        'with_issuer',
        'with_hidden',
        'model_type',
        'creator_id',
    ];

    protected $casts = [
        'with_issuer' => 'bool',
        'with_hidden' => 'bool',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'events' => 'array',
    ];

    protected array $criteria = [
        'name' => Like::class,
        'url' => Like::class,
    ];

    protected array $sortable = [
        'id',
        'name',
        'url',
        'created_at',
        'updated_at',
    ];

    protected string $defaultSortBy = 'created_at';
    protected string $defaultSortDirection = 'desc';

    public function logs(): HasMany
    {
        return $this->hasMany(WebHookEventLogEntry::class);
    }

    public function getEventsAttribute($value)
    {
        return json_decode($value);
    }

    public function setEventsAttribute($value): void
    {
        $this->attributes['events'] = json_encode($value);
    }

    public function hasWebHooks(): MorphTo
    {
        return $this->morphTo('webhooks', 'model_type', 'creator_id', 'id');
    }
}
