<?php

namespace App\Models;

use App\Enums\AuthProviderKey;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
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
    ];

    protected $casts = [
        'provider' => AuthProviderKey::class,
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
