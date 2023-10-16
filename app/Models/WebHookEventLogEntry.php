<?php

namespace App\Models;

use App\Criteria\WebhookIsSuccessful;
use App\Criteria\WhereHasEventType;
use Heseya\Searchable\Traits\HasCriteria;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @mixin IdeHelperWebHookEventLogEntry
 */
class WebHookEventLogEntry extends Model
{
    use HasCriteria;
    use HasFactory;

    public $timestamps;

    protected $fillable = [
        'id',
        'event',
        'web_hook_id',
        'triggered_at',
        'url',
        'status_code',
        'payload',
        'response',
    ];

    protected $casts = [
        'triggered_at' => 'datetime',
        'payload' => 'array',
    ];

    protected array $criteria = [
        'status_code',
        'web_hook_id',
        'event' => WhereHasEventType::class,
        'successful' => WebhookIsSuccessful::class,
    ];

    public function webHook(): BelongsTo
    {
        return $this->belongsTo(WebHook::class);
    }
}
