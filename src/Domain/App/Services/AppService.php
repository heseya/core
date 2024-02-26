<?php

declare(strict_types=1);

namespace Domain\App\Services;

use App\Enums\ExceptionsEnums\Exceptions;
use App\Enums\RoleType;
use App\Enums\TokenType;
use App\Exceptions\ClientException;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Services\Contracts\MetadataServiceContract;
use App\Services\Contracts\UrlServiceContract;
use Domain\App\Dtos\AppInstallDto;
use Domain\App\Dtos\AppUpdatePermissionsDto;
use Domain\App\Models\App;
use Domain\Auth\Services\TokenService;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\App as AppFacade;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Spatie\LaravelData\Optional;
use Throwable;

final class AppService
{
    public function __construct(
        protected TokenService $tokenService,
        protected UrlServiceContract $urlService,
        protected MetadataServiceContract $metadataService,
    ) {}

    /**
     * @throws ClientException
     */
    public function install(AppInstallDto $dto): App
    {
        $allPermissions = Permission::all()->map(fn (Permission $perm) => $perm->name);
        $permissions = Collection::make($dto->allowed_permissions);

        if ($permissions->diff($allPermissions)->isNotEmpty()) {
            throw new ClientException(Exceptions::CLIENT_ASSIGN_INVALID_PERMISSIONS);
        }

        $this->checkUserHasAppPermissions($permissions);

        $appConfig = $this->getAppRootData($dto->url);

        $this->checkAppPermissions($appConfig, $permissions, $allPermissions);

        $name = $dto->name ?? $appConfig['name'];
        $slug = Str::slug($name);

        /** @var App $app */
        $app = App::query()->create([
            'url' => $dto->url,
            'name' => $name,
            'slug' => $slug,
            'licence_key' => $dto->licence_key,
        ] + Collection::make($appConfig)->only([
            'microfrontend_url',
            'version',
            'api_version',
            'description',
            'icon',
            'author',
        ])->toArray());

        $app->givePermissionTo(
            $permissions->concat(['auth.check_identity']),
        );

        $uuid = Str::uuid()->toString();
        $integrationToken = $this->tokenService->createToken(
            $app,
            TokenType::ACCESS,
            $uuid,
        );

        $refreshToken = $this->tokenService->createToken(
            $app,
            TokenType::REFRESH,
            $uuid,
        );

        $url = $this->urlService->urlAppendPath($dto->url, '/install');

        /** @var UrlServiceContract $urlService */
        $urlService = AppFacade::make(UrlServiceContract::class);
        try {
            /** @var Response $response */
            $response = Http::post($url, [
                'api_url' => $urlService->normalizeUrl(Config::get('app.url')),
                'api_name' => Config::get('app.name'),
                'api_version' => Config::get('app.ver'),
                'licence_key' => $dto->licence_key,
                'integration_token' => $integrationToken,
                'refresh_token' => $refreshToken,
            ]);
        } catch (Throwable) {
            throw new ClientException(Exceptions::CLIENT_FAILED_TO_CONNECT_WITH_APP);
        }

        if ($response->failed()) {
            $app->delete();

            throw new ClientException(Exceptions::CLIENT_APP_INSTALLATION_RESPONDED_WITH_INVALID_CODE, null, false, ["Status code: {$response->status()}", "Body: {$response->body()}"]);
        }
        if (!$this->isResponseValid($response, [
            'uninstall_token' => ['required', 'string', 'max:255'],
        ])) {
            $app->delete();

            throw new ClientException(Exceptions::CLIENT_INVALID_INSTALLATION_RESPONSE, null, false, ["Body: {$response->body()}"]);
        }

        $app->update([
            'uninstall_token' => $response->json('uninstall_token'),
        ]);

        /** @phpstan-ignore-next-line */
        $internalPermissions = $this->createInternalPermissions(collect($appConfig['internal_permissions']), $app->slug);

        if ($internalPermissions->isNotEmpty()) {
            if (Auth::user() instanceof User) {
                $this->createAppOwnerRole($app, $internalPermissions);
            }

            $this->makePermissionsPublic(
                $app,
                $internalPermissions,
                Collection::make($dto->public_app_permissions),
            );
        }

        if (!($dto->metadata instanceof Optional)) {
            $this->metadataService->sync($app, $dto->metadata);
        }

        return $app;
    }

    public function uninstall(App $app, bool $force = false): void
    {
        $url = $app->url . (Str::endsWith($app->url, '/') ? 'uninstall' : '/uninstall');

        try {
            /** @var Response $response */
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

        Permission::query()
            ->where('name', 'like', 'app.' . $app->slug . '%')
            ->delete();

        Artisan::call('permission:cache-reset');

        $app->role()->delete();
        $app->webhooks()->delete();
        $app->delete();
    }

    public function appPermissionPrefix(App $app): string
    {
        return 'app.' . $app->slug . '.';
    }

    /**
     * @throws ClientException
     */
    public function updatePermissions(App $app, AppUpdatePermissionsDto $dto): App
    {
        $allPermissions = Permission::all()->map(fn (Permission $perm) => $perm->name);
        $permissions = Collection::make($dto->allowed_permissions);

        if ($permissions->diff($allPermissions)->isNotEmpty()) {
            throw new ClientException(Exceptions::CLIENT_ASSIGN_INVALID_PERMISSIONS);
        }

        $appConfig = $this->getAppRootData($app->url);

        $this->checkAppPermissions($appConfig, $permissions, $allPermissions);

        /**
         * Checking differences in public_app_permissions.
         *
         * @phpstan-ignore-next-line
         */
        $internalPermissions = collect($appConfig['internal_permissions']);

        if (
            !$this->sameCollections(
                Collection::make($dto->public_app_permissions),
                $internalPermissions->filter(fn ($permission) => $permission['unauthenticated'])->pluck('name'),
            )
        ) {
            throw new ClientException(Exceptions::CLIENT_APP_PERMISSIONS_DIFFERENCES);
        }

        // Checking app current permissions
        $currentAppPermissions = $app->getPermissionNames()->filter(fn ($permission) => $permission !== 'auth.check_identity');
        $changes = !$this->sameCollections($currentAppPermissions, $permissions);

        /** @var Role|null $role */
        $role = $app->role;

        if (!$changes) {
            $changes = ($internalPermissions->count() === 0 && $role !== null) || ($internalPermissions->count() > 0 && $role === null);
        }

        $internalPermissionsNames = Collection::make($internalPermissions)->map(fn ($permission) => "app.{$app->slug}.{$permission['name']}");
        if (!$changes && $role instanceof Role) {
            $changes = !$this->sameCollections($role->getPermissionNames(), $internalPermissionsNames);
        }

        if (!$changes) {
            /** @var Role $unauthenticated */
            $unauthenticated = Role::query()->where('type', RoleType::UNAUTHENTICATED->value)->firstOrFail();

            $unauthenticatedPermissions = $unauthenticated->getPermissionNames()->filter(fn ($permission) => Str::startsWith($permission, "app.{$app->slug}."));
            $changes = !$this->sameCollections($unauthenticatedPermissions, $internalPermissions->filter(fn ($permission) => $permission['unauthenticated'])->pluck('name')->map(fn ($permission) => "app.{$app->slug}.{$permission}"));
        }

        if (!$changes) {
            throw new ClientException(Exceptions::CLIENT_APP_NO_PERMISSIONS_CHANGES);
        }

        $this->checkUserHasAppPermissions($permissions->diff($currentAppPermissions));

        $app->syncPermissions($permissions->concat(['auth.check_identity']));

        if ($role instanceof Role) {
            if ($internalPermissions->count() === 0) {
                $role->permissions()->delete();
                $role->delete();
            } else {
                Permission::query()->whereIn('name', $role->getPermissionNames()->diff($internalPermissionsNames)->toArray())->delete();

                // only new permissions
                $internalPermissions = $this
                    ->createInternalPermissions(
                        $internalPermissions->filter(fn ($permission) => in_array($permission['name'], $internalPermissionsNames->diff($role->getPermissionNames())->map(fn ($p) => Str::after($p, "app.{$app->slug}."))->toArray(), true)),
                        $app->slug,
                    );

                $this->makePermissionsPublic(
                    $app,
                    $internalPermissions,
                    Collection::make($dto->public_app_permissions),
                );

                $role->givePermissionTo($internalPermissions);
            }
        } elseif ($internalPermissions->count() > 0) {
            $internalPermissions = $this->createInternalPermissions($internalPermissions, $app->slug);

            if (Auth::user() instanceof User) {
                $this->createAppOwnerRole($app, $internalPermissions);
            }

            $this->makePermissionsPublic(
                $app,
                $internalPermissions,
                Collection::make($dto->public_app_permissions),
            );
        }

        return $app;
    }

    /**
     * @return array<string, mixed>
     *
     * @throws ClientException
     */
    private function getAppRootData(string $url): array
    {
        try {
            $response = Http::get($url);
        } catch (Throwable) {
            throw new ClientException(Exceptions::CLIENT_FAILED_TO_CONNECT_WITH_APP);
        }

        if ($response->failed()) {
            throw new ClientException(Exceptions::CLIENT_APP_INFO_RESPONDED_WITH_INVALID_CODE, null, false, ['code' => $response->status(), 'body' => $response->body()]);
        }

        if (!$this->isAppRootValid($response)) {
            throw new ClientException(Exceptions::CLIENT_APP_RESPONDED_WITH_INVALID_INFO, null, false, ["Body: {$response->body()}"]);
        }

        return $response->json();
    }

    /**
     * @param array<string, mixed> $appConfig
     * @param Collection<int, string> $permissions
     * @param Collection<int, string> $allPermissions
     *
     * @throws ClientException
     */
    private function checkAppPermissions(array $appConfig, Collection $permissions, Collection $allPermissions): void
    {
        /** @var Collection<int, mixed> $requiredPermissions */
        $requiredPermissions = $appConfig['required_permissions'];

        $requiredPerm = Collection::make($requiredPermissions);
        $optionalPerm = array_key_exists('optional_permissions', $appConfig) ?
            $appConfig['optional_permissions'] : [];
        $advertisedPerm = $requiredPerm->concat($optionalPerm)->unique();

        if ($advertisedPerm->diff($allPermissions)->isNotEmpty()) {
            throw new ClientException(Exceptions::CLIENT_APP_WANTS_INVALID_PERMISSION);
        }

        if ($permissions->intersect($requiredPerm)->count() < $requiredPerm->count()) {
            throw new ClientException(Exceptions::CLIENT_ADD_APP_WITHOUT_REQUIRED_PERMISSIONS);
        }

        if ($permissions->intersect($advertisedPerm)->count() < $permissions->count()) {
            throw new ClientException(Exceptions::CLIENT_ADD_PERMISSION_APP_DOESNT_WANT);
        }
    }

    /**
     * @param Collection<int, string> $permissions
     *
     * @throws ClientException
     */
    private function checkUserHasAppPermissions(Collection $permissions): void
    {
        if (!Auth::user()?->hasAllPermissions($permissions->toArray())) {
            throw new ClientException(Exceptions::CLIENT_ADD_APP_WITH_PERMISSIONS_USER_DONT_HAVE);
        }
    }

    /**
     * @param Collection<int, string> $a
     * @param Collection<int, string> $b
     */
    private function sameCollections(Collection $a, Collection $b): bool
    {
        return $a->diff($b)->count() === 0 && $b->diff($a)->count() === 0;
    }

    /**
     * @param Collection<int, array<string, mixed>> $internalPermissions
     *
     * @return Collection<int, Permission>
     */
    private function createInternalPermissions(Collection $internalPermissions, string $slug): Collection
    {
        $internalPermissions = Collection::make($internalPermissions)
            ->map(fn ($permission) => Permission::create([
                'name' => "app.{$slug}.{$permission['name']}",
                'display_name' => $permission['display_name'] ?? null,
                'description' => $permission['description'] ?? null,
            ]));

        /** @var Role $owner */
        $owner = Role::query()->where('type', RoleType::OWNER->value)->firstOrFail();
        $owner->givePermissionTo($internalPermissions);

        return $internalPermissions;
    }

    protected function isAppRootValid(Response $response): bool
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

    /**
     * @param array<string, mixed> $rules
     */
    protected function isResponseValid(Response $response, array $rules): bool
    {
        if ($response->json() === null) {
            return false;
        }

        $validator = Validator::make($response->json(), $rules);

        return !$validator->fails();
    }

    /**
     * Create app owner role and assign it to the current user.
     *
     * @param Collection<int, Permission> $internalPermissions
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

        Auth::user()?->assignRole($role);
    }

    /**
     * Grant unauthenticated users public app permissions.
     *
     * @param Collection<int, Permission> $internalPermissions
     * @param Collection<int, string> $publicPermissions
     */
    private function makePermissionsPublic(
        App $app,
        Collection $internalPermissions,
        Collection $publicPermissions,
    ): void {
        $publicPermissions = $publicPermissions->map(
            fn ($permission) => "app.{$app->slug}.{$permission}",
        );

        /** @var Role $unauthenticated */
        $unauthenticated = Role::where('type', RoleType::UNAUTHENTICATED->value)->firstOrFail();

        $internalPermissions->each(
            fn ($permission) => !$publicPermissions->contains($permission->name)
                ?: $unauthenticated->givePermissionTo($permission),
        );
    }
}
