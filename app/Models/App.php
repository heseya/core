<?php

namespace App\Models;

use Illuminate\Auth\Authenticatable;
use Illuminate\Contracts\Auth\Access\Authorizable as AuthorizableContract;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\Access\Authorizable;
use Tymon\JWTAuth\Contracts\JWTSubject;

/**
 * @mixin IdeHelperApp
 */
class App extends Model implements
    AuthorizableContract,
    AuthenticatableContract,
    JWTSubject
{
    use HasFactory,
        Authorizable,
        Authenticatable;

    protected $fillable = [
        'name',
        'key',
        'url',
    ];

    public function getJWTIdentifier(): string
    {
        return $this->getKey();
    }

    public function getJWTCustomClaims(): array
    {
        return [];
    }
}
