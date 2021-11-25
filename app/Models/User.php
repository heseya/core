<?php

namespace App\Models;

use App\SearchTypes\UserSearch;
use App\Traits\HasWebHooks;
use Heseya\Searchable\Searches\Like;
use Heseya\Searchable\Traits\Searchable;
use Heseya\Sortable\Sortable;
use Illuminate\Auth\Authenticatable;
use Illuminate\Auth\MustVerifyEmail;
use Illuminate\Auth\Passwords\CanResetPassword;
use Illuminate\Contracts\Auth\Access\Authorizable as AuthorizableContract;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Contracts\Auth\CanResetPassword as CanResetPasswordContract;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\Access\Authorizable;
use Illuminate\Notifications\Notifiable;
use OwenIt\Auditing\Auditable;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;
use Spatie\Permission\Traits\HasRoles;
use Tymon\JWTAuth\Contracts\JWTSubject;

/**
 * @mixin IdeHelperUser
 */
class User extends Model implements
    AuthenticatableContract,
    AuthorizableContract,
    CanResetPasswordContract,
    AuditableContract,
    JWTSubject
{
    use Notifiable,
        Authenticatable,
        Authorizable,
        CanResetPassword,
        MustVerifyEmail,
        HasFactory,
        HasRoles,
        SoftDeletes,
        Searchable,
        Sortable,
        Auditable,
        HasWebHooks;

    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected array $searchable = [
        'name' => Like::class,
        'email' => Like::class,
        'search' => UserSearch::class,
    ];

    protected array $sortable = [
        'name',
        'created_at',
        'updated_at',
    ];

    /**
     * Url to avatar.
     */
    public function getAvatarAttribute(): string
    {
        return '//www.gravatar.com/avatar/' . md5(strtolower(trim($this->email))) . '?d=mp&s=50x50';
    }

    public function getJWTIdentifier(): ?string
    {
        return $this->getKey();
    }

    public function getJWTCustomClaims(): array
    {
        return [];
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }
}
