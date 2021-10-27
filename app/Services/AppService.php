<?php

namespace App\Services;

use App\Dtos\AppInstallDto;
use App\Enums\RoleType;
use App\Enums\TokenType;
use App\Exceptions\AppException;
use App\Exceptions\AuthException;
use App\Models\App;
use App\Models\Permission;
use App\Models\Role;
use App\Services\Contracts\AppServiceContract;
use App\Services\Contracts\TokenServiceContract;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Throwable;

class AppService implements AppServiceContract
{
    public function __construct(protected TokenServiceContract $tokenService)
    {
    }

    public function install(AppInstallDto $dto): App
    {
        $allPermissions = Permission::all()->map(fn ($perm) => $perm->name);
        $permissions = Collection::make($dto->getAllowedPermissions());

        if ($permissions->diff($allPermissions)->isNotEmpty()) {
            throw new AuthException('Assigning invalid permissions');
        }

        if (!Auth::user()->hasAllPermissions($permissions->toArray())) {
            throw new AuthException(
                "Can't add an app with permissions you don't have",
            );
        }

        try {
            $response = Http::get($dto->getUrl());
        } catch (Throwable) {
            throw new AppException('Failed to connect with application');
        }

        if ($response->failed()) {
            throw new AppException('Failed to connect with application');
        }

        $this->validateAppRoot($response);

        $appConfig = $response->json();
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

        $requiredPerm = Collection::make($appConfig['required_permissions']);
        $optionalPerm = key_exists('optional_permissions', $appConfig) ?
            $appConfig['optional_permissions'] : [];
        $advertisedPerm = $requiredPerm->concat($optionalPerm)->unique();

        if ($advertisedPerm->diff($allPermissions)->isNotEmpty()) {
            $app->delete();

            throw new AuthException('App wants invalid permissions');
        }

        if ($permissions->intersect($requiredPerm)->count() < $requiredPerm->count()) {
            $app->delete();

            throw new AuthException(
                "Can't add app without all required permissions",
            );
        }

        if ($permissions->intersect($advertisedPerm)->count() < $permissions->count()) {
            $app->delete();

            throw new AuthException(
                "Can't add any permissions application doesn't want",
            );
        }

        $app->givePermissionTo(
            $permissions->concat(['auth.login', 'auth.identity_profile']),
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

        $url = Str::endsWith($dto->getUrl(), '/')
            ? "{$dto->getUrl()}install"
            : "{$dto->getUrl()}/install";

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
            throw new AppException('Failed to connect with application');
        }

        if ($response->failed()) {
            $app->delete();

            throw new AppException('Failed to install the application');
        }

        $validator = Validator::make($response->json(), [
            'uninstall_token' => ['required', 'string'],
        ]);

        if ($validator->fails()) {
            $app->delete();

            throw new AppException('App has invalid installation response');
        }

        $uninstallToken = $response->json('uninstall_token');

        $internalPermissions = Collection::make($appConfig['internal_permissions']);

        $internalPermissions = $internalPermissions->map(fn ($permission) => Permission::create([
            'name' => "app.{$app->slug}.{$permission['name']}",
            'description' => key_exists('description', $permission) ?
                $permission['description'] : null,
        ]));

        $owner = Role::where('type', RoleType::OWNER)->firstOrFail();
        $owner->givePermissionTo($internalPermissions);

        if ($internalPermissions->isNotEmpty()) {
            // Create app owner role and assign it to the user
            $role = Role::create([
                'name' => $app->name . ' owner',
            ]);
            $role->syncPermissions($internalPermissions);

            $app->update([
                'role_id' => $role->getKey(),
                'uninstall_token' => $uninstallToken,
            ]);

            Auth::user()->assignRole($role);

            // Grant unauthenticated user public app permissions
            $publicPermissions = Collection::make($dto->getPublicAppPermissions())
                ->map(fn ($permission) => "app.{$app->slug}.{$permission}");

            /** @var Role $unauthenticated */
            $unauthenticated = Role::where('type', RoleType::UNAUTHENTICATED)->firstOrFail();

            $internalPermissions->each(
                fn ($permission) => !$publicPermissions->contains($permission->name)
                    ?: $unauthenticated->givePermissionTo($permission),
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
        } catch (Throwable) {
            if (!$force) {
                throw new AppException('Failed to connect with application');
            }
        }

        if (!$force && $response->failed()) {
            throw new AppException('Failed to uninstall the application');
        }

        Permission::where('name', 'like', 'app.' . $app->slug . '%')->delete();
        $app->role()->delete();
        $app->delete();
    }

    public function appPermissionPrefix(App $app): string
    {
        return 'app.' . $app->slug . '.';
    }

    protected function validateAppRoot($response)
    {
        $validator = Validator::make($response->json(), [
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

        if ($validator->fails()) {
            throw new AppException('App responded with invalid info');
        }
    }
}
