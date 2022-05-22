<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @mixin IdeHelperWebHookEventLogEntry
 */
class WebHookEventLogEntry extends Model
{
    use HasFactory;

    public $timestamps = null;

    protected $fillable = [
        'id',
        'web_hook_id',
        'triggered_at',
        'url',
        'status_code',
        'payload',
        'response',
    ];

    protected $casts = [
        'triggered_at' => 'datetime',
    ];

    public function webHook(): BelongsTo
    {
        return $this->belongsTo(WebHook::class);
    }
}
