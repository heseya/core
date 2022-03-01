<?php

namespace App\Models;

use App\Services\Contracts\UrlServiceContract;
use App\Traits\HasWebHooks;
use Illuminate\Auth\Authenticatable;
use Illuminate\Contracts\Auth\Access\Authorizable as AuthorizableContract;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Foundation\Auth\Access\Authorizable;
use Illuminate\Support\Facades\App as AppFacade;
use PHPOpenSourceSaver\JWTAuth\Contracts\JWTSubject;
use Spatie\Permission\Contracts\Permission;
use Spatie\Permission\Traits\HasPermissions;

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
        HasPermissions,
        HasWebHooks;

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

    public function setUrlAttribute(string $url): void
    {
        /** @var UrlServiceContract $urlService */
        $urlService = AppFacade::make(UrlServiceContract::class);

        $this->attributes['url'] = $urlService->normalizeUrl($url);
    }

    public function getJWTIdentifier(): string
    {
        return $this->getKey();
    }

    public function getJWTCustomClaims(): array
    {
        return [];
    }

    public function orders(): MorphMany
    {
        return $this->morphMany(Order::class, 'user');
    }

    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }

    public function hasRole($roles, ?string $guard = null): bool
    {
        return false;
    }

    protected function hasPermissionViaRole(Permission $permission): bool
    {
        return false;
    }
}
