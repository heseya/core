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
use App\Services\Contracts\MetadataServiceContract;
use App\Services\Contracts\TokenServiceContract;
use App\Services\Contracts\UrlServiceContract;
use Domain\App\Dtos\AppConfigDto;
use Domain\App\Dtos\AppWidgetCreateDto;
use Domain\App\Dtos\InternalPermissionDto;
use Domain\App\Services\AppWidgetService;
use Heseya\Dto\Missing;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Spatie\LaravelData\DataCollection;
use Spatie\LaravelData\Optional;
use Throwable;

class AppService implements AppServiceContract
{
    public function __construct(
        protected TokenServiceContract $tokenService,
        protected UrlServiceContract $urlService,
        protected MetadataServiceContract $metadataService,
        protected AppWidgetService $appWidgetService,
    ) {}

    public function install(AppInstallDto $dto): App
    {
        $allPermissions = Permission::all()->map(fn (Permission $perm) => $perm->name);
        $permissions = Collection::make($dto->getAllowedPermissions());

        $clientInvalidPermissions = $permissions->diff($allPermissions);
        if ($clientInvalidPermissions->isNotEmpty()) {
            throw new ClientException(Exceptions::CLIENT_ASSIGN_INVALID_PERMISSIONS, null, false, ['permissions' => $clientInvalidPermissions->toArray()]);
        }

        /** @var User|App|null $user */
        $user = Auth::user();

        if (empty($user) || !$user->hasAllPermissions($permissions->toArray())) {
            $userPermissions = $user?->getAllPermissions()->pluck('name') ?? [];

            throw new ClientException(Exceptions::CLIENT_ADD_APP_WITH_PERMISSIONS_USER_DONT_HAVE, null, false, ['permissions' => $permissions->diff($userPermissions)]);
        }

        try {
            /** @var Response $response */
            $response = Http::get($dto->getUrl());
        } catch (Throwable) {
            throw new ClientException(Exceptions::CLIENT_FAILED_TO_CONNECT_WITH_APP);
        }

        if ($response->failed()) {
            throw new ClientException(Exceptions::CLIENT_APP_INFO_RESPONDED_WITH_INVALID_CODE, null, false, ['code' => $response->status(), 'body' => $response->body()]);
        }

        try {
            $appConfig = AppConfigDto::from($response->json());
        } catch (Throwable $th) {
            throw new ClientException(Exceptions::CLIENT_APP_RESPONDED_WITH_INVALID_INFO, $th, false, ["Body: {$response->body()}"]);
        }

        $requiredPerm = Collection::make($appConfig->required_permissions);
        $advertisedPerm = $requiredPerm->concat(is_array($appConfig->optional_permissions) ? $appConfig->optional_permissions : [])->unique();

        $appInvalidPermissions = $advertisedPerm->diff($allPermissions);
        if ($appInvalidPermissions->isNotEmpty()) {
            throw new ClientException(Exceptions::CLIENT_APP_WANTS_INVALID_PERMISSION, null, false, ['permissions' => $appInvalidPermissions->toArray()]);
        }

        $missingPermissions = $requiredPerm->diff($permissions->intersect($requiredPerm));
        if ($missingPermissions->isNotEmpty()) {
            throw new ClientException(Exceptions::CLIENT_ADD_APP_WITHOUT_REQUIRED_PERMISSIONS, null, false, ['permissions' => $missingPermissions->toArray()]);
        }

        $unnecessaryPermissions = $permissions->diff($permissions->intersect($advertisedPerm));
        if ($unnecessaryPermissions->isNotEmpty()) {
            throw new ClientException(Exceptions::CLIENT_ADD_PERMISSION_APP_DOESNT_WANT, null, false, ['permissions' => $unnecessaryPermissions->toArray()]);
        }

        $name = $dto->getName() ?? $appConfig->name;
        $slug = Str::slug($name);

        $app = App::query()->create([
            'url' => $dto->getUrl(),
            'name' => $name,
            'slug' => $slug,
            'licence_key' => $dto->getLicenceKey(),
        ] + $appConfig->only(
            'microfrontend_url',
            'version',
            'api_version',
            'description',
            'icon',
            'author',
        )->toArray());

        assert($app instanceof App);

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

        $url = $this->urlService->urlAppendPath($dto->getUrl(), '/install');

        try {
            /** @var Response $response */
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

        /** @var Collection<int,Permission> */
        $internalPermissions = collect($appConfig->internal_permissions->map(fn (InternalPermissionDto $internalPermissionDto) => Permission::create([
            'name' => "app.{$app->slug}.{$internalPermissionDto->name}",
            'display_name' => $internalPermissionDto->display_name instanceof Optional ? null : $internalPermissionDto->display_name,
            'description' => $internalPermissionDto->description instanceof Optional ? null : $internalPermissionDto->description,
        ]))->items());

        $owner = Role::where('type', RoleType::OWNER->value)->firstOrFail();
        $owner->givePermissionTo($internalPermissions);

        if ($internalPermissions->isNotEmpty()) {
            if ($user instanceof User) {
                $this->createAppOwnerRole($user, $app, $internalPermissions);
            }

            $this->makePermissionsPublic(
                $app,
                $internalPermissions,
                Collection::make($dto->getPublicAppPermissions()),
            );
        }

        if (!($dto->getMetadata() instanceof Missing)) {
            $this->metadataService->sync($app, $dto->getMetadata());
        }

        if ($appConfig->widgets instanceof DataCollection && $app->hasPermissionTo('app_widgets.add')) {
            $appConfig->widgets->each(fn (AppWidgetCreateDto $appWidgetCreateDto) => $this->appWidgetService->create($appWidgetCreateDto, $app));
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
     */
    private function createAppOwnerRole(User $user, App $app, Collection $internalPermissions): void
    {
        $role = Role::create([
            'name' => $app->name . ' owner',
        ]);
        $role->syncPermissions($internalPermissions);

        $app->update([
            'role_id' => $role->getKey(),
        ]);

        $user->assignRole($role);
    }

    /**
     * Grant unauthenticated users public app permissions.
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
            fn (Permission $permission) => !$publicPermissions->contains($permission->name)
                ?: $unauthenticated->givePermissionTo($permission),
        );
    }
}
