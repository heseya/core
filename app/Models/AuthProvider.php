<?php

namespace App\Models;

use App\Enums\AuthProviderKey;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * @property AuthProviderKey $key;
 *
 * @mixin IdeHelperAuthProvider
 */
class AuthProvider extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'key',
        'active',
        'client_id',
        'client_secret',
    ];

    protected $casts = [
        'active' => 'boolean',
        'key' => AuthProviderKey::class,
    ];
}
