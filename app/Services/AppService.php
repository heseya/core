<?php

namespace App\Services;

use App\Dtos\AppInstallDto;
use App\Enums\ExceptionsEnums\Exceptions;
use App\Enums\RoleType;
use App\Enums\TokenType;
use App\Exceptions\ClientException;
use App\Models\App;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Services\Contracts\AppServiceContract;
use App\Services\Contracts\TokenServiceContract;
use App\Services\Contracts\UrlServiceContract;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Throwable;

class AppService implements AppServiceContract
{
    public function __construct(
        protected TokenServiceContract $tokenService,
        protected UrlServiceContract $urlService,
    ) {
    }

    public function install(AppInstallDto $dto): App
    {
        $allPermissions = Permission::all()->map(fn (Permission $perm) => $perm->name);
        $permissions = Collection::make($dto->getAllowedPermissions());

        if ($permissions->diff($allPermissions)->isNotEmpty()) {
            throw new ClientException(Exceptions::CLIENT_ASSIGN_INVALID_PERMISSIONS);
        }

        if (!Auth::user()->hasAllPermissions($permissions->toArray())) {
            throw new ClientException(Exceptions::CLIENT_ADD_APP_WITH_PERMISSIONS_USER_DONT_HAVE);
        }

        try {
            $response = Http::get($dto->getUrl());
        } catch (Throwable) {
            throw new ClientException(Exceptions::CLIENT_FAILED_TO_CONNECT_WITH_APP);
        }

        if ($response->failed()) {
            throw new ClientException(
                Exceptions::CLIENT_APP_RESPONDED_WITH_INVALID_CODE,
                0,
                null,
                false,
                [
                    "Status code: {$response->status()}",
                    "Body: {$response->body()}",
                ],
            );
        }

        if (!$this->isAppRootValid($response)) {
            throw new ClientException(
                Exceptions::CLIENT_APP_RESPONDED_WITH_INVALID_INFO,
                0,
                null,
                false,
                [
                    "Body: {$response->body()}",
                ],
            );
        }

        $appConfig = $response->json();

        $requiredPerm = Collection::make($appConfig['required_permissions']);
        $optionalPerm = key_exists('optional_permissions', $appConfig) ?
            $appConfig['optional_permissions'] : [];
        $advertisedPerm = $requiredPerm->concat($optionalPerm)->unique();

        if ($advertisedPerm->diff($allPermissions)->isNotEmpty()) {
            throw new ClientException(Exceptions::CLIENT_APP_WANTS_INVALID_INFO);
        }

        if ($permissions->intersect($requiredPerm)->count() < $requiredPerm->count()) {
            throw new ClientException(Exceptions::CLIENT_ADD_APP_WITHOUT_REQUIRED_PERMISSIONS);
        }

        if ($permissions->intersect($advertisedPerm)->count() < $permissions->count()) {
            throw new ClientException(Exceptions::CLIENT_ADD_PERMISSION_AP_DOESNT_WANT);
        }

        $name = $dto->getName() ?? $appConfig['name'];
        $slug = Str::slug($name);

        $app = App::create([
            'url' => $dto->getUrl(),
            'name' => $name,
            'slug' => $slug,
            'licence_key' => $dto->getLicenceKey(),
        ] + Collection::make($appConfig)->only([
            'microfrontend_url',
            'version',
            'api_version',
            'description',
            'icon',
            'author',
        ])->toArray());

        $app->givePermissionTo(
            $permissions->concat(['auth.login', 'auth.check_identity']),
        );

        $uuid = Str::uuid()->toString();
        $integrationToken = $this->tokenService->createToken(
            $app,
            new TokenType(TokenType::ACCESS),
            $uuid,
        );

        $refreshToken = $this->tokenService->createToken(
            $app,
            new TokenType(TokenType::REFRESH),
            $uuid,
        );

        $url = $this->urlService->urlAppendPath($dto->getUrl(), '/install');

        try {
            $response = Http::post($url, [
                'api_url' => Config::get('app.url'),
                'api_name' => Config::get('app.name'),
                'api_version' => Config::get('app.ver'),
                'licence_key' => $dto->getLicenceKey(),
                'integration_token' => $integrationToken,
                'refresh_token' => $refreshToken,
            ]);
        } catch (Throwable) {
            throw new ClientException(Exceptions::CLIENT_FAILED_TO_CONNECT_WITH_APP);
        }

        if ($response->failed()) {
            $app->delete();

            throw new ClientException(
                Exceptions::CLIENT_APP_RESPONDED_WITH_INVALID_CODE,
                0,
                null,
                false,
                [
                    "Status code: {$response->status()}",
                    "Body: {$response->body()}",
                ],
            );
        }
        if (!$this->isResponseValid($response, [
            'uninstall_token' => ['required', 'string', 'max:255'],
        ])) {
            $app->delete();

            throw new ClientException(
                Exceptions::CLIENT_INVALID_INSTALLATION_RESPONSE,
                0,
                null,
                false,
                [
                    "Body: {$response->body()}",
                ],
            );
        }

        $app->update([
            'uninstall_token' => $response->json('uninstall_token'),
        ]);

        $internalPermissions = Collection::make($appConfig['internal_permissions'])
            ->map(fn ($permission) => Permission::create([
                'name' => "app.{$app->slug}.{$permission['name']}",
                'display_name' => $permission['display_name'] ?? null,
                'description' => $permission['description'] ?? null,
            ]));

        $owner = Role::where('type', RoleType::OWNER)->firstOrFail();
        $owner->givePermissionTo($internalPermissions);

        if ($internalPermissions->isNotEmpty()) {
            if (Auth::user() instanceof User) {
                $this->createAppOwnerRole($app, $internalPermissions);
            }

            $this->makePermissionsPublic(
                $app,
                $internalPermissions,
                Collection::make($dto->getPublicAppPermissions()),
            );
        }

        return $app;
    }

    public function uninstall(App $app, bool $force = false): void
    {
        $url = $app->url . (Str::endsWith($app->url, '/') ? 'uninstall' : '/uninstall');

        try {
            $response = Http::post($url, [
                'uninstall_token' => $app->uninstall_token,
            ]);

            if (!$force && $response->failed()) {
                throw new ClientException(Exceptions::CLIENT_FAILED_TO_UNINSTALL_APP);
            }
        } catch (Throwable) {
            if (!$force) {
                throw new ClientException(Exceptions::CLIENT_FAILED_TO_CONNECT_WITH_APP);
            }
        }

        Permission::where('name', 'like', 'app.' . $app->slug . '%')->delete();

        Artisan::call('permission:cache-reset');

        $app->role()->delete();
        $app->webhooks()->delete();
        $app->delete();
    }

    public function appPermissionPrefix(App $app): string
    {
        return 'app.' . $app->slug . '.';
    }

    protected function isAppRootValid($response)
    {
        return $this->isResponseValid($response, [
            'name' => ['required', 'string'],
            'author' => ['required', 'string'],
            'version' => ['required', 'string'],
            'api_version' => ['required', 'string'],
            'description' => ['nullable', 'string'],
            'microfrontend_url' => ['nullable', 'string'],
            'icon' => ['nullable', 'string'],
            'licence_required' => ['nullable', 'boolean'],
            'required_permissions' => ['array'],
            'required_permissions.*' => ['string'],
            'optional_permissions' => ['array'],
            'optional_permissions.*' => ['string'],
            'internal_permissions' => ['array'],
            'internal_permissions.*' => ['array'],
            'internal_permissions.*.name' => ['required', 'string'],
            'internal_permissions.*.description' => ['nullable', 'string'],
        ]);
    }

    protected function isResponseValid($response, $rules)
    {
        if ($response->json() === null) {
            return false;
        }

        $validator = Validator::make($response->json(), $rules);

        return !$validator->fails();
    }

    /**
     * Create app owner role and assign it to the current user
     *
     * @param App $app
     * @param Collection $internalPermissions
     */
    private function createAppOwnerRole(App $app, Collection $internalPermissions): void
    {
        $role = Role::create([
            'name' => $app->name . ' owner',
        ]);
        $role->syncPermissions($internalPermissions);

        $app->update([
            'role_id' => $role->getKey(),
        ]);

        Auth::user()->assignRole($role);
    }

    /**
     * Grant unauthenticated users public app permissions
     *
     * @param App $app
     * @param Collection $internalPermissions
     * @param Collection $publicPermissions
     */
    private function makePermissionsPublic(
        App $app,
        Collection $internalPermissions,
        Collection $publicPermissions
    ): void {
        $publicPermissions = $publicPermissions->map(
            fn ($permission) => "app.{$app->slug}.{$permission}",
        );

        /** @var Role $unauthenticated */
        $unauthenticated = Role::where('type', RoleType::UNAUTHENTICATED)->firstOrFail();

        $internalPermissions->each(
            fn ($permission) => !$publicPermissions->contains($permission->name)
                ?: $unauthenticated->givePermissionTo($permission),
        );
    }
}
