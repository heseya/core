<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WebHookEventLogEntry extends Model
{
    use HasFactory;

    protected $fillable = [
        'web_hook_id',
        'triggered_at',
        'url',
        'status_code',
    ];

    protected $casts = [
        'triggered_at' => 'datetime',
    ];

    public function webHook(): BelongsTo
    {
        return $this->belongsTo(WebHook::class);
    }
}
