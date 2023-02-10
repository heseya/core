<?php

namespace App\Models;

use App\Enums\AuthProviderKey;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property User $user
 * @mixin IdeHelperUserProvider
 */
class UserProvider extends Model
{
    public $timestamps = false;
    protected $table = 'user_providers';

    protected $fillable = [
        'provider',
        'provider_user_id',
        'user_id',
        'merge_token',
        'merge_token_expires_at',
    ];

    protected $casts = [
        'provider' => AuthProviderKey::class,
    ];

    protected $dates = [
        'merge_token_expires_at',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
