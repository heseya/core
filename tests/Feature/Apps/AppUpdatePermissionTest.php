<?php

namespace Tests\Feature\Apps;

use App\Enums\ExceptionsEnums\Exceptions;
use App\Enums\RoleType;
use App\Models\Permission;
use App\Models\Role;
use Domain\App\Models\App;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class AppUpdatePermissionTest extends TestCase
{
    use RefreshDatabase;

    private App $permissionApp;

    public function setUp(): void
    {
        parent::setUp();

        $this->permissionApp = App::factory()->create([
            'url' => 'https://example-permissions.com',
        ]);
    }

    public function testUpdatePermissionUnauthorized(): void
    {
        $this
            ->json('POST', '/apps/id:' . $this->permissionApp->getKey() . '/update-permissions')
            ->assertForbidden();
    }

    public function testUpdatePermissionDifferentPayload(): void
    {
        $this->user->givePermissionTo('apps.install');

        $this->permissionApp->givePermissionTo(['products.show']);

        $this->mockAppRoot();

        $this->actingAs($this->user)->json('POST', '/apps/id:' . $this->permissionApp->getKey() . '/update-permissions', [
            'allowed_permissions' => [
                'products.show',
            ],
            'public_app_permissions' => [
                'product_layout',
            ],
        ])
            ->assertUnprocessable()
            ->assertJsonFragment([
                'key' => Exceptions::CLIENT_ADD_APP_WITHOUT_REQUIRED_PERMISSIONS->name,
                'message' => Exceptions::CLIENT_ADD_APP_WITHOUT_REQUIRED_PERMISSIONS->value,
            ]);
    }

    public function testUpdatePermissionUserNoHavePermission(): void
    {
        $this->user->givePermissionTo('apps.install');

        $this->permissionApp->givePermissionTo(['products.show']);

        $this->prepareAppNoAllPermissions();

        $this->mockAppRoot();

        $this->actingAs($this->user)->json('POST', '/apps/id:' . $this->permissionApp->getKey() . '/update-permissions', [
            'allowed_permissions' => [
                'products.show',
                'products.show_hidden',
            ],
            'public_app_permissions' => [
                'product_layout_show',
            ],
        ])
            ->assertUnprocessable()
            ->assertJsonFragment([
                'key' => Exceptions::CLIENT_ADD_APP_WITH_PERMISSIONS_USER_DONT_HAVE->name,
                'message' => Exceptions::CLIENT_ADD_APP_WITH_PERMISSIONS_USER_DONT_HAVE->value,
            ]);
    }

    public function testUpdatePermissionNoPublicAppPermissionsPayload(): void
    {
        $this->user->givePermissionTo(['apps.install', 'products.show', 'products.show_hidden']);

        $this->permissionApp->givePermissionTo(['products.show', 'products.show_hidden']);

        $this->mockAppRoot();

        $this->actingAs($this->user)->json('POST', '/apps/id:' . $this->permissionApp->getKey() . '/update-permissions', [
            'allowed_permissions' => [
                'products.show',
                'products.show_hidden',
            ],
        ])
            ->assertUnprocessable()
            ->assertJsonFragment([
                'key' => Exceptions::CLIENT_APP_PERMISSIONS_DIFFERENCES->name,
                'message' => Exceptions::CLIENT_APP_PERMISSIONS_DIFFERENCES->value,
            ]);
    }

    public function testUpdatePermissionNoPermissionsChanges(): void
    {
        $this->user->givePermissionTo(['apps.install', 'products.show', 'products.show_hidden']);

        $this->prepareAppPermissions();

        $this->mockAppRoot();

        $this->actingAs($this->user)->json('POST', '/apps/id:' . $this->permissionApp->getKey() . '/update-permissions', [
            'allowed_permissions' => [
                'products.show',
                'products.show_hidden',
            ],
            'public_app_permissions' => [
                'product_layout_show',
            ],
        ])
            ->assertUnprocessable()
            ->assertJsonFragment([
                'key' => Exceptions::CLIENT_APP_NO_PERMISSIONS_CHANGES->name,
                'message' => Exceptions::CLIENT_APP_NO_PERMISSIONS_CHANGES->value,
            ]);
    }

    public function testUpdatePermissionNoPreviousPermissions(): void
    {
        $this->user->givePermissionTo(['apps.install', 'products.show', 'products.show_hidden']);

        $this->mockAppRoot();

        $this->actingAs($this->user)->json('POST', '/apps/id:' . $this->permissionApp->getKey() . '/update-permissions', [
            'allowed_permissions' => [
                'products.show',
                'products.show_hidden',
            ],
            'public_app_permissions' => [
                'product_layout_show',
            ],
        ])
            ->assertOk()
            ->assertJsonFragment([
                'id' => $this->permissionApp->getKey(),
            ])
            ->assertJsonFragment([
                'permissions' => [
                    'auth.check_identity',
                    'products.show',
                    'products.show_hidden',
                ],
            ]);

        $this->assertDatabaseHas('permissions', [
            'name' => "app.{$this->permissionApp->slug}.product_layout_show",
        ]);

        /** @var Role $unauthenticated */
        $unauthenticated = Role::query()->where('type', RoleType::UNAUTHENTICATED)->firstOrFail();
        $this->assertTrue($unauthenticated->hasPermissionTo("app.{$this->permissionApp->slug}.product_layout_show"));

        /** @var Role $owner */
        $owner = Role::query()->where('type', RoleType::OWNER)->firstOrFail();
        $this->assertTrue($owner->hasPermissionTo("app.{$this->permissionApp->slug}.product_layout_show"));
        $this->assertTrue($owner->hasPermissionTo("app.{$this->permissionApp->slug}.product_layout"));

        /** @var Role $role */
        $role = Role::query()->where('name', '=', $this->permissionApp->name . ' owner')->first();
        $this->assertTrue($role->hasPermissionTo("app.{$this->permissionApp->slug}.product_layout"));
        $this->assertTrue($role->hasPermissionTo("app.{$this->permissionApp->slug}.product_layout_show"));
    }

    public function testUpdatePermissionNewPermissions(): void
    {
        $this->user->givePermissionTo(['apps.install', 'products.show', 'products.show_hidden']);

        $this->prepareAppNoAllPermissions();

        $this->mockAppRoot();

        $this->actingAs($this->user)->json('POST', '/apps/id:' . $this->permissionApp->getKey() . '/update-permissions', [
            'allowed_permissions' => [
                'products.show',
                'products.show_hidden',
            ],
            'public_app_permissions' => [
                'product_layout_show',
            ],
        ])
            ->assertOk()
            ->assertJsonFragment([
                'id' => $this->permissionApp->getKey(),
            ])
            ->assertJsonFragment([
                'permissions' => [
                    'auth.check_identity',
                    'products.show',
                    'products.show_hidden',
                ],
            ]);

        $this->assertDatabaseHas('permissions', [
            'name' => "app.{$this->permissionApp->slug}.product_layout_show",
        ]);

        /** @var Role $unauthenticated */
        $unauthenticated = Role::query()->where('type', RoleType::UNAUTHENTICATED)->firstOrFail();
        $this->assertTrue($unauthenticated->hasPermissionTo("app.{$this->permissionApp->slug}.product_layout_show"));

        /** @var Role $owner */
        $owner = Role::query()->where('type', RoleType::OWNER)->firstOrFail();
        $this->assertTrue($owner->hasPermissionTo("app.{$this->permissionApp->slug}.product_layout_show"));

        /** @var Role $role */
        $role = Role::query()->where('name', '=', $this->permissionApp->name . ' owner')->first();
        $this->assertTrue($role->hasPermissionTo("app.{$this->permissionApp->slug}.product_layout_show"));
    }

    public function testUpdatePermissionAppWithoutInternalPermissions(): void
    {
        $this->user->givePermissionTo(['apps.install', 'products.show', 'products.show_hidden']);

        $this->prepareAppPermissions();

        $this->mockAppRootNoInternalPermissions();

        $this->actingAs($this->user)->json('POST', '/apps/id:' . $this->permissionApp->getKey() . '/update-permissions', [
            'allowed_permissions' => [
                'products.show',
                'products.show_hidden',
            ],
        ])
            ->assertOk()
            ->assertJsonFragment([
                'id' => $this->permissionApp->getKey(),
            ])
            ->assertJsonFragment([
                'permissions' => [
                    'auth.check_identity',
                    'products.show',
                    'products.show_hidden',
                ],
            ]);

        $this->assertDatabaseMissing('permissions', [
            'name' => "app.{$this->permissionApp->slug}.product_layout_show",
        ]);

        $this->assertDatabaseMissing('permissions', [
            'name' => "app.{$this->permissionApp->slug}.product_layout",
        ]);
    }

    private function mockAppRoot(): void
    {
        Http::fake([
            $this->permissionApp->url => Http::response([
                'name' => 'App name',
                'author' => 'Mr. Author',
                'version' => '1.0.0',
                'api_version' => '^2.0.0',
                'description' => 'Cool description',
                'microfrontend_url' => 'https://front.example.com',
                'icon' => 'https://picsum.photos/200',
                'licence_required' => false,
                'required_permissions' => [
                    'products.show',
                    'products.show_hidden',
                ],
                'internal_permissions' => [
                    [
                        'name' => 'product_layout',
                        'description' => 'Setup layouts of products page',
                        'unauthenticated' => false,
                    ],
                    [
                        'name' => 'product_layout_show',
                        'description' => 'Show layouts of products page',
                        'unauthenticated' => true,
                    ],
                ],
            ])
        ]);
    }

    private function mockAppRootNoInternalPermissions(): void
    {
        Http::fake([
            $this->permissionApp->url => Http::response([
                'name' => 'App name',
                'author' => 'Mr. Author',
                'version' => '1.0.0',
                'api_version' => '^2.0.0',
                'description' => 'Cool description',
                'microfrontend_url' => 'https://front.example.com',
                'icon' => 'https://picsum.photos/200',
                'licence_required' => false,
                'required_permissions' => [
                    'products.show',
                    'products.show_hidden',
                ],
                'internal_permissions' => [],
            ])
        ]);
    }

    private function prepareAppPermissions(): void
    {
        $prefix = "app.{$this->permissionApp->slug}.";
        $role = Role::query()->create([
            'name' => $this->permissionApp->name . ' owner',
        ]);

        $this->permissionApp->update([
            'role_id' => $role->getKey(),
        ]);
        $appPermission1 = Permission::create([
            'name' => $prefix . 'product_layout',
            'description' => 'Setup layouts of products page',
        ]);

        $appPermission2 = Permission::create([
            'name' => $prefix . 'product_layout_show',
            'description' => 'Setup layouts of products page',
        ]);

        $unauthenticated = Role::query()->where('type', RoleType::UNAUTHENTICATED)->firstOrFail();

        $role->givePermissionTo([$prefix . 'product_layout', $prefix . 'product_layout_show']);
        $unauthenticated->givePermissionTo([$prefix . 'product_layout_show']);
        $this->permissionApp->givePermissionTo(['products.show', 'products.show_hidden']);
    }

    private function prepareAppNoAllPermissions(): void
    {
        $prefix = "app.{$this->permissionApp->slug}.";
        $role = Role::query()->create([
            'name' => $this->permissionApp->name . ' owner',
        ]);

        $this->permissionApp->update([
            'role_id' => $role->getKey(),
        ]);
        $appPermission1 = Permission::create([
            'name' => $prefix . 'product_layout',
            'description' => 'Setup layouts of products page',
        ]);

        $role->givePermissionTo([$prefix . 'product_layout']);
        $this->permissionApp->givePermissionTo(['products.show']);
    }
}
