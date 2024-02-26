<?php

declare(strict_types=1);

namespace Domain\App\Models;

use App\Criteria\MetadataPrivateSearch;
use App\Criteria\MetadataSearch;
use App\Criteria\WhereInIds;
use App\Enums\SavedAddressType;
use App\Models\FavouriteProductSet;
use App\Models\Model;
use App\Models\Order;
use App\Models\Role;
use App\Models\SavedAddress;
use App\Services\Contracts\UrlServiceContract;
use App\Traits\HasMetadata;
use App\Traits\HasWebHooks;
use App\Traits\HasWishlist;
use Domain\ShippingMethod\Models\ShippingMethod;
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
final class App extends Model implements AuthenticatableContract, AuthorizableContract, JWTSubject
{
    use Authenticatable;
    use Authorizable;
    use HasCriteria;
    use HasFactory;
    use HasMetadata;
    use HasPermissions;
    use HasWebHooks;
    use HasWishlist;

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
        'refresh_token_key',
    ];

    /** @var string[] */
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

    /**
     * @return array<int, mixed>
     */
    public function getJWTCustomClaims(): array
    {
        return [];
    }

    /**
     * @return HasMany<SavedAddress>
     */
    public function shippingAddresses(): HasMany
    {
        return $this->hasMany(SavedAddress::class, 'user_id')
            ->where('type', '=', SavedAddressType::SHIPPING->value);
    }

    /**
     * @return HasMany<SavedAddress>
     */
    public function billingAddresses(): HasMany
    {
        return $this->hasMany(SavedAddress::class, 'user_id')
            ->where('type', '=', SavedAddressType::BILLING->value);
    }

    /**
     * @return MorphMany<Order>
     */
    public function orders(): MorphMany
    {
        return $this->morphMany(Order::class, 'buyer');
    }

    /**
     * @return BelongsTo<Role, self>
     */
    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }

    /**
     * @return HasMany<ShippingMethod>
     */
    public function shippingMethods(): HasMany
    {
        return $this->hasMany(ShippingMethod::class);
    }

    /**
     * @param array<int, Role>|Collection<int, Role>|int|\Spatie\Permission\Contracts\Role|string $roles
     */
    public function hasRole(
        array|Collection|int|\Spatie\Permission\Contracts\Role|string $roles,
        ?string $guard = null,
    ): bool {
        return false;
    }

    /**
     * @return MorphMany<FavouriteProductSet>
     */
    public function favouriteProductSets(): MorphMany
    {
        return $this->morphMany(FavouriteProductSet::class, 'user');
    }

    protected function hasPermissionViaRole(Permission $permission): bool
    {
        return false;
    }
}
