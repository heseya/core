<?php

namespace App\Models;

use App\Criteria\ConsentIdSearch;
use App\Criteria\ConsentNameSearch;
use App\Criteria\MetadataPrivateSearch;
use App\Criteria\MetadataSearch;
use App\Criteria\RolesSearch;
use App\Criteria\UserSearch;
use App\Criteria\WhereInIds;
use App\Enums\SavedAddressType;
use App\Enums\TFAType;
use App\Models\Contracts\SortableContract;
use App\Traits\HasDiscountConditions;
use App\Traits\HasMetadata;
use App\Traits\HasWebHooks;
use App\Traits\HasWishlist;
use App\Traits\Sortable;
use Domain\Consent\Models\Consent;
use Domain\Consent\Models\ConsentUser;
use Domain\Metadata\Models\MetadataPersonal;
use Domain\Organization\Models\Organization;
use Heseya\Searchable\Criteria\Like;
use Heseya\Searchable\Traits\HasCriteria;
use Illuminate\Auth\Authenticatable;
use Illuminate\Auth\MustVerifyEmail;
use Illuminate\Auth\Passwords\CanResetPassword;
use Illuminate\Contracts\Auth\Access\Authorizable as AuthorizableContract;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Contracts\Auth\CanResetPassword as CanResetPasswordContract;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\Access\Authorizable;
use Illuminate\Notifications\Notifiable;
use PHPOpenSourceSaver\JWTAuth\Contracts\JWTSubject;
use Propaganistas\LaravelPhone\PhoneNumber;
use Spatie\Permission\Traits\HasRoles;

/**
 * @property string|null $remember_token
 *
 * @mixin IdeHelperUser
 */
class User extends Model implements AuthenticatableContract, AuthorizableContract, CanResetPasswordContract, JWTSubject, SortableContract
{
    use Authenticatable;
    use Authorizable;
    use CanResetPassword;
    use HasCriteria;
    use HasDiscountConditions;
    use HasFactory;
    use HasMetadata;
    use HasRoles;
    use HasWebHooks;
    use HasWishlist;
    use MustVerifyEmail;
    use Notifiable;
    use SoftDeletes;
    use Sortable;

    // Bez tego nie działały testy, w których jako aplikacja tworzy się użytkownika z określoną rolą
    protected string $guard_name = 'api';
    protected $fillable = [
        'name',
        'email',
        'password',
        'tfa_type',
        'tfa_secret',
        'is_tfa_active',
        'preferences_id',
        'birthday_date',
        'phone_country',
        'phone_number',
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
        'consent_name' => ConsentNameSearch::class,
        'consent_id' => ConsentIdSearch::class,
        'roles' => RolesSearch::class,
    ];
    protected array $sortable = [
        'name',
        'created_at',
        'updated_at',
    ];
    protected $casts = [
        'is_tfa_active' => 'bool',
        'tfa_type' => TFAType::class,
    ];

    /**
     * Url to avatar.
     */
    public function getAvatarAttribute(): string
    {
        return '//www.gravatar.com/avatar/' . md5(mb_strtolower(trim($this->email))) . '?d=mp&s=50x50';
    }

    public function getPhoneAttribute(): ?string
    {
        return $this->phone_number !== null && $this->phone_country !== null
            ? new PhoneNumber($this->phone_number, $this->phone_country) : null;
    }

    public function getJWTIdentifier(): string
    {
        return $this->getKey() ?? 'null';
    }

    public function getJWTCustomClaims(): array
    {
        return [];
    }

    public function shippingAddresses(): HasMany
    {
        return $this->hasMany(SavedAddress::class)
            ->where('type', '=', SavedAddressType::SHIPPING->value);
    }

    public function billingAddresses(): HasMany
    {
        return $this->hasMany(SavedAddress::class)
            ->where('type', '=', SavedAddressType::BILLING->value);
    }

    public function orders(): MorphMany
    {
        return $this->morphMany(Order::class, 'buyer');
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

    public function preferences(): BelongsTo
    {
        return $this->belongsTo(UserPreference::class, 'preferences_id');
    }

    public function loginAttempts(): HasMany
    {
        return $this->hasMany(UserLoginAttempt::class, 'user_id', 'id');
    }

    public function favouriteProductSets(): MorphMany
    {
        return $this->morphMany(FavouriteProductSet::class, 'user');
    }

    public function providers(): HasMany
    {
        return $this->hasMany(UserProvider::class, 'user_id', 'id');
    }

    public function metadataPersonal(): MorphMany
    {
        return $this->morphMany(MetadataPersonal::class, 'model', 'model_type', 'model_id');
    }

    public function organizations(): BelongsToMany
    {
        return $this->belongsToMany(Organization::class, 'organization_user');
    }
}
