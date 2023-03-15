<?php

namespace App\Services;

use App\Dtos\AuthProviderDto;
use App\Dtos\AuthProviderLoginDto;
use App\Dtos\AuthProviderMergeAccountDto;
use App\Enums\AuthProviderKey;
use App\Enums\ExceptionsEnums\Exceptions;
use App\Enums\RoleType;
use App\Exceptions\ClientException;
use App\Http\Resources\AuthProviderListResource;
use App\Models\AuthProvider;
use App\Models\Role;
use App\Models\User;
use App\Models\UserProvider;
use App\Services\Contracts\AuthServiceContract;
use App\Services\Contracts\ProviderServiceContract;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;
use Throwable;

class ProviderService implements ProviderServiceContract
{
    public function __construct(
        private AuthServiceContract $authService,
    ) {
    }

    public function getProvidersList(bool|null $active): JsonResource
    {
        $query = AuthProvider::query();

        if ($active !== null) {
            $query->where('active', $active);
        }

        return AuthProviderListResource::collection(
            $query->paginate(Config::get('pagination.per_page')),
        );
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
            // @phpstan-ignore-next-line
            $user = Socialite::driver($authProviderKey)->stateless()->user();
        } catch (Throwable $exception) {
            throw new ClientException(Exceptions::CLIENT_INVALID_CREDENTIALS);
        }

        $id = $user->getId();

        $apiUserQuery = User::query()
            ->whereHas('providers', function (Builder $query) use ($authProviderKey, $id) {
                return $query->where([
                    'provider' => $authProviderKey,
                    'provider_user_id' => $id,
                ])
                    ->whereNull('merge_token');
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
            $existingUser = User::query()->where('email', $user->getEmail())->first();
            if ($existingUser) {
                $mergeToken = Str::random(128);
                $existingUser->providers()->updateOrCreate([
                    'provider' => $authProviderKey,
                    'provider_user_id' => $id,
                    'user_id' => $existingUser->getKey(),
                ], [
                    'merge_token' => $mergeToken,
                    'merge_token_expires_at' => Carbon::now()->addDay(),
                ]);

                throw new ClientException(Exceptions::CLIENT_ALREADY_HAS_ACCOUNT, errorArray: [
                    'merge_token' => $mergeToken,
                ]);
            }
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

    public function mergeAccount(AuthProviderMergeAccountDto $dto): void
    {
        try {
            $userProvider = UserProvider::query()
                ->with('user')
                ->where('merge_token', $dto->getMergeToken())
                ->firstOrFail();
        } catch (Throwable $e) {
            throw new ClientException(Exceptions::CLIENT_PROVIDER_MERGE_TOKEN_INVALID);
        }

        if (Auth::user()?->email !== $userProvider->user->email) {
            throw new ClientException(Exceptions::CLIENT_PROVIDER_MERGE_TOKEN_MISMATCH);
        }

        if (Carbon::now()->isAfter($userProvider->merge_token_expires_at)) {
            throw new ClientException(Exceptions::CLIENT_PROVIDER_MERGE_TOKEN_EXPIRED);
        }

        $userProvider->update([
            'merge_token' => null,
            'merge_token_expires_at' => null,
        ]);
    }
}
