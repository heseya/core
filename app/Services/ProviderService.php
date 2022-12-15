<?php

namespace App\Services;

use App\Dtos\AuthProviderDto;
use App\Dtos\AuthProviderLoginDto;
use App\Enums\AuthProviderKey;
use App\Enums\ExceptionsEnums\Exceptions;
use App\Enums\RoleType;
use App\Exceptions\ClientException;
use App\Http\Resources\AuthProviderListResource;
use App\Models\AuthProvider;
use App\Models\Role;
use App\Models\User;
use App\Services\Contracts\AuthServiceContract;
use App\Services\Contracts\ProviderServiceContract;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Config;
use Laravel\Socialite\Facades\Socialite;
use Throwable;

class ProviderService implements ProviderServiceContract
{
    public function __construct(private AuthServiceContract $authService)
    {
    }

    public function getProvidersList(bool|null $active): JsonResource
    {
        $providers = AuthProvider::all();

        $enums = Collection::make(AuthProviderKey::getInstances());

        $list = Collection::make();

        $enums->each(function ($enum) use ($providers, $list): void {
            $provider = $providers->where('key', $enum->value)->first();
            if ($provider !== null && $provider->client_id !== null && $provider->client_secret !== null) {
                $list->push($provider);
            } else {
                $list->push(new AuthProvider([
                    'key' => $enum->value,
                    'active' => false,
                ]));
            }
        });

        if ($active !== null) {
            $list = $list->filter(function (AuthProvider $value) use ($active) {
                return $value->active === $active;
            });
        }

        return AuthProviderListResource::collection($list);
    }

    public function getProvider(string $authProviderKey): ?AuthProvider
    {
        $providerEnum = AuthProviderKey::fromValue($authProviderKey);
        return AuthProvider::query()->where('key', $providerEnum->value)->first();
    }

    public function update(AuthProviderDto $dto, AuthProvider $provider): AuthProvider
    {
        $provider->update($dto->toArray());
        return $provider;
    }

    public function setupRedirect(string $authProviderKey, string $returnUrl): void
    {
        $providerQuery = AuthProvider::query()->where('key', $authProviderKey);

        if (!$providerQuery->exists()) {
            throw new ClientException(Exceptions::CLIENT_PROVIDER_NOT_FOUND);
        }

        /** @var AuthProvider $provider */
        $provider = $providerQuery->first();

        if (!$provider->active) {
            throw new ClientException(Exceptions::CLIENT_PROVIDER_IS_NOT_ACTIVE);
        }

        if ($provider->client_id === null || $provider->client_secret === null) {
            throw new ClientException(Exceptions::CLIENT_PROVIDER_HAS_NO_CONFIG);
        }

        Config::set("services.{$authProviderKey}.client_id", $provider->client_id);
        Config::set("services.{$authProviderKey}.client_secret", $provider->client_secret);
        Config::set("services.{$authProviderKey}.redirect", $returnUrl);
    }

    public function login(string $authProviderKey, AuthProviderLoginDto $dto): array
    {
        /** @var AuthProvider $provider */
        $provider = AuthProvider::query()->where('key', $authProviderKey)->first();

        Config::set("services.{$authProviderKey}.client_id", $provider->client_id);
        Config::set("services.{$authProviderKey}.client_secret", $provider->client_secret);
        Config::set("services.{$authProviderKey}.redirect", $dto->getReturnUrl());

        request()->merge($dto->getParams());
        try {
            $user = Socialite::driver($authProviderKey)->stateless()->user();
        } catch (Throwable $exception) {
            throw new ClientException(Exceptions::CLIENT_INVALID_CREDENTIALS);
        }

        $id = $user->getId();

        $apiUserQuery = User::query()->whereHas('providers', function (Builder $query) use ($authProviderKey, $id) {
            return $query->where([
                'provider' => $authProviderKey,
                'provider_user_id' => $id,
            ]);
        });

        if ($apiUserQuery->exists()) {
            /** @var Authenticatable $apiUser */
            $apiUser = $apiUserQuery->first();

            $data = $this->authService->loginWithUser(
                $apiUser,
                $dto->getIp(),
                $dto->getUserAgent(),
            );
        } else {
            $newUser = User::create([
                'name' => $user->getName(),
                'email' => $user->getEmail(),
            ]);
            $newUser->providers()->create([
                'provider' => $authProviderKey,
                'provider_user_id' => $id,
                'user_id' => $newUser->getKey(),
            ]);

            /** @var Role $authenticated */
            $authenticated = Role::where('type', RoleType::AUTHENTICATED)->first();
            $newUser->syncRoles($authenticated);

            $data = $this->authService->loginWithUser(
                $newUser,
                $dto->getIp(),
                $dto->getUserAgent(),
            );
        }
        return $data;
    }
}
