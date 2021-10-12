<?php

namespace App\Models;

use Illuminate\Auth\Authenticatable;
use Illuminate\Contracts\Auth\Access\Authorizable as AuthorizableContract;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\Access\Authorizable;
use Spatie\Permission\Contracts\Permission;
use Spatie\Permission\Traits\HasPermissions;
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
        Authenticatable,
        HasPermissions;

    protected $guard_name = 'api';

    protected $fillable = [
        'url',
        'microfrontend_url',
        'name',
        'slug',
        'version',
        'api_version',
        'licence_key',
        'description',
        'icon',
        'author',
        'uninstall_token',
        'role_id',
    ];

    public function getJWTIdentifier(): string
    {
        return $this->getKey();
    }

    public function getJWTCustomClaims(): array
    {
        return [];
    }

    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }

    protected function hasPermissionViaRole(Permission $permission): bool
    {
        return false;
    }
}
