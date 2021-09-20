<?php

namespace App\Services;

use App\Enums\TokenType;
use App\Exceptions\AuthException;
use App\Models\App;
use App\Models\Permission;
use App\Services\Contracts\AppServiceContract;
use App\Services\Contracts\TokenServiceContract;
use Exception;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class AppService implements AppServiceContract
{
    public function __construct(protected TokenServiceContract $tokenService)
    {
    }

    public function install(
        string $url,
        array $permissions,
        ?string $name,
        ?string $licenceKey,
    ): App {
        if (!Auth::user()->hasAllPermissions($permissions)) {
            throw new AuthException(
                'Can\'t add an app with permissions you don\'t have',
            );
        }

        $response = Http::get($url);

        if ($response->failed()) {
            throw new Exception();
        }

        $appConfig = $response->json();
        $name = $name ?? $appConfig['name'];
        $slug = Str::slug($name);

        $this->validateAppRoot($response);

        $app = App::create([
            'url' => $url,
            'name' => $name,
            'slug' => $slug,
            'licence_key' => $licenceKey,
        ] + Collection::make($appConfig)->only([
            'microfrontend_url',
            'version',
            'api_version',
            'description',
            'icon',
            'author',
        ])->toArray());

        $permissions = Collection::make($permissions);
        $requiredPerm = Collection::make($appConfig['required_permissions']);
        $optionalPerm = key_exists('optional_permissions', $appConfig) ?
            $appConfig['optional_permissions'] : [];
        $advertisedPerm = $requiredPerm->concat($optionalPerm)->unique();

        $allPermissions = Permission::all()->map(fn ($perm) => $perm->name);

        if ($advertisedPerm->diff($allPermissions)->isNotEmpty()) {
            throw new AuthException('App wants invalid permissions');
        }

        if ($permissions->intersect($requiredPerm)->count() < $requiredPerm->count()) {
            throw new AuthException(
                'Can\'t add app without all required permissions',
            );
        }

        if ($permissions->intersect($advertisedPerm)->count() < $permissions->count()) {
            throw new AuthException(
                'Can\'t add any permissions application doesn\'t want',
            );
        }

        $app->givePermissionTo(
            $permissions->concat(['auth.login', 'auth.identity_profile']),
        );

        $integrationToken = $this->tokenService->createToken(
            $app,
            new TokenType(TokenType::ACCESS)
        );

        $refreshToken = $this->tokenService->createToken(
            $app,
            new TokenType(TokenType::REFRESH)
        );

        $url .= Str::endsWith($url, '/') ? 'install' : '/install';

        $response = Http::post($url, [
            'api_url' => Config::get('app.url'),
            'api_name' => Config::get('app.name'),
            'api_version' => Config::get('app.var'),
            'licence_key' => $licenceKey,
            'integration_token' => $integrationToken,
            'refresh_token' => $refreshToken,
        ]);

        if ($response->failed()) {
            throw new Exception();
        }

        $uninstallToken = $response->json('uninstall_token');

        $app->update([
            'uninstall_token' => $uninstallToken,
        ]);

        return $app;
    }

    protected function validateAppRoot($response)
    {
        Validator::validate($response->json(), [
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
            'internal_permissions' => ['array'],
            'internal_permissions.*' => ['array'],
            'internal_permissions.*.name' => ['required', 'string'],
            'internal_permissions.*.description' => ['nullable', 'string'],
        ]);
    }
}
