<?php

namespace App\Models;

use App\Criteria\MetadataPrivateSearch;
use App\Criteria\MetadataSearch;
use App\Criteria\UserSearch;
use App\Criteria\WhereInIds;
use App\Traits\HasMetadata;
use App\Traits\HasWebHooks;
use App\Traits\Sortable;
use Heseya\Searchable\Criteria\Like;
use Heseya\Searchable\Traits\HasCriteria;
use Illuminate\Auth\Authenticatable;
use Illuminate\Auth\MustVerifyEmail;
use Illuminate\Auth\Passwords\CanResetPassword;
use Illuminate\Contracts\Auth\Access\Authorizable as AuthorizableContract;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Contracts\Auth\CanResetPassword as CanResetPasswordContract;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\Access\Authorizable;
use Illuminate\Notifications\Notifiable;
use OwenIt\Auditing\Auditable;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;
use PHPOpenSourceSaver\JWTAuth\Contracts\JWTSubject;
use Spatie\Permission\Traits\HasRoles;

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
        HasCriteria,
        Sortable,
        Auditable,
        HasWebHooks,
        HasMetadata;

    // Bez tego nie działały testy, w których jako aplikacja tworzy się użytkownika z określoną rolą
    protected $guard_name = 'api';

    protected $fillable = [
        'name',
        'email',
        'password',
        'tfa_type',
        'tfa_secret',
        'is_tfa_active',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected array $criteria = [
        'name' => Like::class,
        'email' => Like::class,
        'search' => UserSearch::class,
        'ids' => WhereInIds::class,
        'metadata' => MetadataSearch::class,
        'metadata_private' => MetadataPrivateSearch::class,
    ];

    protected array $sortable = [
        'name',
        'created_at',
        'updated_at',
    ];

    protected $casts = [
        'is_tfa_active' => 'bool',
    ];

    /**
     * Url to avatar.
     */
    public function getAvatarAttribute(): string
    {
        return '//www.gravatar.com/avatar/' . md5(strtolower(trim($this->email))) . '?d=mp&s=50x50';
    }

    public function getJWTIdentifier(): string
    {
        return $this->getKey() ?? 'null';
    }

    public function getJWTCustomClaims(): array
    {
        return [];
    }

    public function orders(): MorphMany
    {
        return $this->morphMany(Order::class, 'user');
    }

    public function consents(): BelongsToMany
    {
        return $this->belongsToMany(Consent::class)
            ->using(ConsentUser::class)
            ->withPivot('value');
    }

    public function securityCodes(): HasMany
    {
        return $this->hasMany(OneTimeSecurityCode::class, 'user_id', 'id');
    }
}
