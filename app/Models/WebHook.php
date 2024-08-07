<?php

namespace App\Models;

use App\Criteria\WebHookSearch;
use App\Criteria\WhereInIds;
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
    use Auditable;
    use HasCriteria;
    use HasFactory;
    use Notifiable;
    use SoftDeletes;
    use Sortable;

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
        'search' => WebHookSearch::class,
        'ids' => WhereInIds::class,
    ];

    protected array $sortable = [
        'id',
        'name',
        'url',
        'created_at',
        'updated_at',
    ];

    public function logs(): HasMany
    {
        return $this->hasMany(WebHookEventLogEntry::class);
    }

    public function getEventsAttribute(string $value): mixed
    {
        return json_decode($value);
    }

    public function setEventsAttribute(mixed $value): void
    {
        $this->attributes['events'] = json_encode($value);
    }

    public function hasWebHooks(): MorphTo
    {
        return $this->morphTo('webhooks', 'model_type', 'creator_id', 'id');
    }
}
