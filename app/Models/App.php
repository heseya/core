<?php

namespace App\Models;

use App\Criteria\MetadataPrivateSearch;
use App\Criteria\MetadataSearch;
use App\Criteria\WhereInIds;
use App\Enums\SavedAddressType;
use App\Services\Contracts\UrlServiceContract;
use App\Traits\HasMetadata;
use App\Traits\HasWebHooks;
use Heseya\Searchable\Traits\HasCriteria;
use Illuminate\Auth\Authenticatable;
use Illuminate\Contracts\Auth\Access\Authorizable as AuthorizableContract;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Foundation\Auth\Access\Authorizable;
use Illuminate\Support\Collection;
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
        HasCriteria,
        Authorizable,
        Authenticatable,
        HasPermissions,
        HasWebHooks,
        HasMetadata;

    protected string $guard_name = 'api';
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

    protected array $criteria = [
        'metadata' => MetadataSearch::class,
        'metadata_private' => MetadataPrivateSearch::class,
        'ids' => WhereInIds::class,
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

    public function shippingAddresses(): HasMany
    {
        return $this->hasMany(SavedAddress::class, 'user_id')
            ->where('type', '=', SavedAddressType::SHIPPING);
    }

    public function billingAddresses(): HasMany
    {
        return $this->hasMany(SavedAddress::class, 'user_id')
            ->where('type', '=', SavedAddressType::BILLING);
    }

    public function orders(): MorphMany
    {
        return $this->morphMany(Order::class, 'buyer');
    }

    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }

    public function shippingMethods(): HasMany
    {
        return $this->hasMany(ShippingMethod::class);
    }

    public function hasRole(
        string|int|array|\Spatie\Permission\Contracts\Role|Collection $roles,
        ?string $guard = null
    ): bool {
        return false;
    }

    public function wishlistProducts(): MorphMany
    {
        return $this->morphMany(WishlistProduct::class, 'user');
    }

    public function favouriteProductSets(): MorphMany
    {
        return $this->morphMany(FavouriteProductSet::class, 'user');
    }

    protected function hasPermissionViaRole(Permission $permission): bool
    {
        return false;
    }
}
