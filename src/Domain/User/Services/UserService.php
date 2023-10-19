<?php

declare(strict_types=1);

namespace Domain\User\Services;

use App\Dtos\SelfUpdateRoles;
use App\Enums\ExceptionsEnums\Exceptions;
use App\Enums\RoleType;
use App\Events\UserCreated;
use App\Events\UserDeleted;
use App\Events\UserUpdated;
use App\Exceptions\ClientException;
use App\Models\DiscountCondition;
use App\Models\Role;
use App\Models\SavedAddress;
use App\Models\User;
use App\Models\UserPreference;
use App\Services\Contracts\MetadataServiceContract;
use Domain\User\Dtos\UserCreateDto;
use Domain\User\Dtos\UserIndexDto;
use Domain\User\Dtos\UserUpdateDto;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Spatie\LaravelData\Optional;

final readonly class UserService
{
    public function __construct(
        private MetadataServiceContract $metadataService,
    ) {}

    /**
     * @return LengthAwarePaginator<User>
     */
    public function index(UserIndexDto $dto, ?string $sort): LengthAwarePaginator
    {
        return User::searchByCriteria($dto->toArray())
            ->sort($sort)
            ->with('metadata')
            ->paginate(Config::get('pagination.per_page'));
    }

    /**
     * @throws ClientException
     */
    public function create(UserCreateDto $dto): User
    {
        if (!$dto->roles instanceof Optional) {
            $roleModels = Role::query()
                ->whereIn('id', $dto->roles)
                ->orWhere('type', RoleType::AUTHENTICATED->value)
                ->get();
        } else {
            $roleModels = Role::query()
                ->where('type', RoleType::AUTHENTICATED->value)
                ->get();
        }

        $permissions = $roleModels->flatMap(
            fn (Role $role) => $role->type !== RoleType::AUTHENTICATED ? $role->getPermissionNames() : [],
        )->unique();

        if (!Auth::user()?->hasAllPermissions($permissions)) {
            throw new ClientException(Exceptions::CLIENT_GIVE_ROLE_THAT_USER_DOESNT_HAVE, simpleLogs: true);
        }

        $fields = $dto->toArray();
        $fields['password'] = Hash::make($dto->password);
        /** @var User $user */
        $user = User::query()->create($fields);

        $preferences = UserPreference::query()->create();
        $preferences->refresh();

        $user->preferences()->associate($preferences);

        $user->syncRoles($roleModels);

        if (!($dto->metadata_computed instanceof Optional)) {
            $this->metadataService->sync($user, $dto->metadata_computed);
        }

        $user->save();

        $user->markEmailAsUnverified();
        $user->sendEmailVerificationNotification();

        UserCreated::dispatch($user);

        return $user;
    }

    /**
     * @throws ClientException
     */
    public function update(User $user, UserUpdateDto $dto): User
    {
        $authenticable = Auth::user();

        if (!$dto->roles instanceof Optional && $dto->roles !== null) {
            /** @var Collection<int, Role> $roleModels */
            $roleModels = Role::query()
                ->whereIn('id', $dto->roles)
                ->orWhere('type', RoleType::AUTHENTICATED->value)
                ->get();

            $newRoles = $roleModels->diff($user->roles);

            /** @var Collection<int, Role> $removedRoles */
            $removedRoles = $user->roles->diff($roleModels);

            $permissions = $newRoles->flatMap(
                fn (Role $role) => $role->type !== RoleType::AUTHENTICATED ? $role->getPermissionNames() : [],
            )->unique();

            if (!$authenticable?->hasAllPermissions($permissions)) {
                throw new ClientException(Exceptions::CLIENT_GIVE_ROLE_THAT_USER_DOESNT_HAVE);
            }

            $permissions = $removedRoles->flatMap(
                fn (Role $role) => $role->type !== RoleType::AUTHENTICATED ? $role->getPermissionNames() : [],
            )->unique();

            if (!$authenticable->hasAllPermissions($permissions)) {
                throw new ClientException(Exceptions::CLIENT_REMOVE_ROLE_THAT_USER_DOESNT_HAVE);
            }

            /** @var Role $owner */
            $owner = Role::query()
                ->where('type', RoleType::OWNER->value)
                ->first();

            if ($newRoles->contains($owner) && !$authenticable->hasRole($owner)) {
                throw new ClientException(Exceptions::CLIENT_ONLY_OWNER_GRANTS_OWNER_ROLE);
            }

            if ($removedRoles->contains($owner)) {
                if (!$authenticable->hasRole($owner)) {
                    throw new ClientException(Exceptions::CLIENT_ONLY_OWNER_REMOVES_OWNER_ROLE);
                }

                $ownerCount = User::query()->whereHas(
                    'roles',
                    fn (Builder $query) => $query->where('type', RoleType::OWNER->value),
                )->count();

                if ($ownerCount < 2) {
                    throw new ClientException(Exceptions::CLIENT_ONE_OWNER_REMAINS);
                }
            }

            $user->syncRoles($roleModels);
        }

        $user->update($dto->toArray());

        UserUpdated::dispatch($user);

        if ($user->wasChanged('email')) {
            $user->markEmailAsUnverified();
            $user->sendEmailVerificationNotification();
        }

        return $user;
    }

    /**
     * @throws ClientException
     */
    public function destroy(User $user): void
    {
        $authenticable = Auth::user();

        $owner = Role::query()->where('type', RoleType::OWNER->value)->firstOrFail();

        if ($user->hasRole($owner)) {
            if (!$authenticable?->hasRole($owner)) {
                throw new ClientException(Exceptions::CLIENT_ONLY_OWNER_REMOVES_OWNER_ROLE);
            }

            $ownerCount = User::query()->whereHas(
                'roles',
                fn (Builder $query) => $query->where('type', RoleType::OWNER->value),
            )->count();

            if ($ownerCount < 2) {
                throw new ClientException(Exceptions::CLIENT_ONE_OWNER_REMAINS);
            }
        }

        DB::transaction(function () use ($user): void {
            // Delete user addresses not bound to orders
            $user->shippingAddresses()->each(function (SavedAddress $address): void {
                if (!$address->address?->orders()->exists()) {
                    $address->address?->delete();
                }
            });
            $user->shippingAddresses()->delete();
            $user->billingAddresses()->each(function (SavedAddress $address): void {
                if (!$address->address?->orders()->exists()) {
                    $address->address?->delete();
                }
            });
            $user->billingAddresses()->delete();

            // Delete user consents
            $user->consents()->delete();

            // Delete user from discount conditions
            // Potentially need to delete user from value json field
            $user->discountConditions()->each(function (DiscountCondition $condition) use ($user): void {
                // Remove user id from the array
                $value = $condition->value;
                $value['users'] = array_diff($value['users'], [$user->id]);
                $condition->update(['value' => $value]);
            });
            $user->discountConditions()->detach();

            // Delete favourite product sets
            $user->favouriteProductSets()->forceDelete();

            // Delete user metadata
            $user->metadata()->delete();
            $user->metadataPrivate()->delete();
            $user->metadataPersonal()->delete();

            // Delete user one time security codes
            $user->securityCodes()->delete();

            // Disassociate orders from the user
            $user->orders()->update([
                'buyer_id' => null,
                'buyer_type' => null,
            ]);

            // Detach user from roles
            $user->roles()->detach();

            // Delete user login attempts
            $user->loginAttempts()->delete();

            // Delete user oauth providers
            $user->providers()->delete();

            // Delete user wishlist
            $user->wishlistProducts()->forceDelete();

            $preferences = $user->preferences;

            $user->fill([
                'name' => 'Deleted user',
                // Emails in database must be unique strings
                'email' => Str::uuid(),
                'password' => null,
                'tfa_type' => null,
                'tfa_secret' => null,
                'is_tfa_active' => false,
                'preferences_id' => null,
                'birthday_date' => null,
                'phone_country' => null,
                'phone_number' => null,
            ]);
            $user->remember_token = null;
            $user->save();

            // Delete user preferences
            $preferences?->delete();

            if ($user->delete()) {
                UserDeleted::dispatch($user);
            }
        });
    }

    public function selfUpdateRoles(User $user, SelfUpdateRoles $dto): User
    {
        $roleModels = collect();
        if (!($dto->roles instanceof Optional)) {
            /** @var Collection<int, Role> $roleModels */
            $roleModels = Role::query()
                ->whereIn('id', $dto->roles)
                ->where('is_joinable', '=', true)
                ->get();

            if ($roleModels->count() < count($dto->roles)) {
                throw new ClientException(Exceptions::CLIENT_JOINING_NON_JOINABLE_ROLE);
            }
        }

        /** @phpstan-ignore-next-line */
        $roleModels = $roleModels->merge($user->roles->filter(fn (Role $role): bool => !$role->is_joinable));

        $user->syncRoles($roleModels);

        return $user;
    }
}
