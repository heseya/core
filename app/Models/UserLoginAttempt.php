<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @mixin IdeHelperUserLoginAttempt
 */
class UserLoginAttempt extends Model
{
    protected $fillable = [
        'user_id',
        'ip',
        'user_agent',
        'fingerprint',
        'logged',
    ];

    protected $casts = [
        'logged' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
