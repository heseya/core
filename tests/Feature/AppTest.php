<?php

namespace Tests\Feature;

use App\Models\App;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Tests\TestCase;

class AppTest extends TestCase
{
    use RefreshDatabase;

    private string $url = 'https://example.com:9000';

    public function testIndexUnauthorized(): void
    {
        $response = $this->getJson('/apps');

        $response->assertForbidden();
    }

    public function testIndex(): void
    {
        $this->user->givePermissionTo('apps.show');

        App::factory()->count(10)->create();

        $response = $this->actingAs($this->user)->getJson('/apps');

        $response
            ->assertOk()
            ->assertJsonCount(10, 'data');
    }

    public function testInstallUnauthorized(): void
    {
        $response = $this->postJson('/apps');

        $response->assertForbidden();
    }

    public function testInstallNotFound(): void
    {
        $this->user->givePermissionTo([
            'apps.install',
            'products.show',
        ]);

        Http::fake([
            $this->url => Http::response([], 404),
        ]);

        $response = $this->actingAs($this->user)->postJson('/apps', [
            'url' => $this->url,
            'allowed_permissions' => [
                'products.show',
            ],
        ]);

        $response->assertStatus(422);
        $this->assertDatabaseCount('apps', 0);
    }

    public function testInstallFailed(): void
    {
        $this->user->givePermissionTo([
            'apps.install',
            'products.show',
        ]);

        Http::fake([
            $this->url => Http::response([
                'name' => 'App name',
                'author' => 'Mr. Author',
                'version' => '1.0.0',
                'api_version' => '^1.4.0', // '^1.2.0' [TODO]
                'description' => 'Cool description',
                'microfrontend_url' => 'https://front.example.com',
                'icon' => 'https://picsum.photos/200',
                'licence_required' => false,
                'required_permissions' => [
                    'products.show',
                ],
                'internal_permissions' => [[
                    'name' => 'product_layout',
                    'description' => 'Setup layouts of products page',
                ]],
            ]),
            $this->url . '/install' => Http::response([], 404),
        ]);

        $response = $this->actingAs($this->user)->postJson('/apps', [
            'url' => $this->url,
            'allowed_permissions' => [
                'products.show',
            ],
        ]);

        $response->assertStatus(422);
        $this->assertDatabaseCount('apps', 0);
    }

    public function testInstallInvalidInfo(): void
    {
        $this->user->givePermissionTo([
            'apps.install',
            'products.show',
        ]);

        Http::fake([
            $this->url => Http::response([]),
        ]);

        $response = $this->actingAs($this->user)->postJson('/apps', [
            'url' => $this->url,
            'allowed_permissions' => [
                'products.show',
            ],
        ]);

        $response->assertStatus(422);
        $this->assertDatabaseCount('apps', 0);
    }

    public function testInstallInvalidInstallationResponse(): void
    {
        $this->user->givePermissionTo([
            'apps.install',
            'products.show',
        ]);

        Http::fake([
            $this->url => Http::response([
                'name' => 'App name',
                'author' => 'Mr. Author',
                'version' => '1.0.0',
                'api_version' => '^1.4.0', // '^1.2.0' [TODO]
                'description' => 'Cool description',
                'microfrontend_url' => 'https://front.example.com',
                'icon' => 'https://picsum.photos/200',
                'licence_required' => false,
                'required_permissions' => [
                    'products.show',
                ],
                'internal_permissions' => [[
                    'name' => 'product_layout',
                    'description' => 'Setup layouts of products page',
                ]],
            ]),
            $this->url . '/install' => Http::response([]),
        ]);

        $response = $this->actingAs($this->user)->postJson('/apps', [
            'url' => $this->url,
            'allowed_permissions' => [
                'products.show',
            ],
        ]);

        $response->assertStatus(422);
        $this->assertDatabaseCount('apps', 0);
    }

    public function testInstall(): void
    {
        $this->user->givePermissionTo([
            'apps.install',
            'products.show',
        ]);

        $uninstallToken = Str::random(128);

        Http::fake([
            $this->url => Http::response([
                'name' => 'App name',
                'author' => 'Mr. Author',
                'version' => '1.0.0',
                'api_version' => '^1.4.0', // '^1.2.0' [TODO]
                'description' => 'Cool description',
                'microfrontend_url' => 'https://front.example.com',
                'icon' => 'https://picsum.photos/200',
                'licence_required' => false,
                'required_permissions' => [
                    'products.show',
                ],
                'internal_permissions' => [[
                    'name' => 'product_layout',
                    'description' => 'Setup layouts of products page',
                ]],
            ]),
            $this->url . '/install' => Http::response([
                'uninstall_token' => $uninstallToken,
            ]),
        ]);

        $response = $this->actingAs($this->user)->postJson('/apps', [
            'url' => $this->url,
            'allowed_permissions' => [
                'products.show',
            ],
        ]);

        $response->assertCreated()
            ->assertJsonFragment([
                'url' => $this->url,
                'microfrontend_url' => 'https://front.example.com',
                'name' => 'App name',
                'slug' => Str::slug('App name'),
                'author' => 'Mr. Author',
                'version' => '1.0.0',
                'description' => 'Cool description',
                'icon' => 'https://picsum.photos/200',
            ]);

        $this->assertDatabaseHas('apps', [
            'name' => 'App name',
            'author' => 'Mr. Author',
            'version' => '1.0.0',
            'api_version' => '^1.4.0',
            'description' => 'Cool description',
            'microfrontend_url' => 'https://front.example.com',
            'icon' => 'https://picsum.photos/200',
            'uninstall_token' => $uninstallToken,
        ]);

        $app = App::where('name', 'App name')->firstOrFail();

        $this->assertTrue($app->hasAllPermissions([
            'auth.login',
            'auth.identity_profile',
            'products.show',
        ]));
    }

    public function testInstallWithOptionalPermissions(): void
    {
        $this->user->givePermissionTo([
            'apps.install',
            'products.show',
            'products.add',
        ]);

        $uninstallToken = Str::random(128);

        Http::fake([
            $this->url => Http::response([
                'name' => 'App name',
                'author' => 'Mr. Author',
                'version' => '1.0.0',
                'api_version' => '^1.4.0', // '^1.2.0' [TODO]
                'description' => 'Cool description',
                'microfrontend_url' => 'https://front.example.com',
                'icon' => 'https://picsum.photos/200',
                'licence_required' => false,
                'required_permissions' => [
                    'products.show',
                ],
                'optional_permissions' => [
                    'products.add',
                ],
                'internal_permissions' => [[
                    'name' => 'product_layout',
                    'description' => 'Setup layouts of products page',
                ]],
            ]),
            $this->url . '/install' => Http::response([
                'uninstall_token' => $uninstallToken,
            ]),
        ]);

        $response = $this->actingAs($this->user)->postJson('/apps', [
            'url' => $this->url,
            'allowed_permissions' => [
                'products.show',
                'products.add',
            ],
        ]);

        $response->assertCreated()
            ->assertJsonFragment([
                'url' => $this->url,
                'microfrontend_url' => 'https://front.example.com',
                'name' => 'App name',
                'slug' => Str::slug('App name'),
                'author' => 'Mr. Author',
                'version' => '1.0.0',
                'description' => 'Cool description',
                'icon' => 'https://picsum.photos/200',
            ]);
        
        $this->assertDatabaseHas('apps', [
            'name' => 'App name',
            'author' => 'Mr. Author',
            'version' => '1.0.0',
            'api_version' => '^1.4.0',
            'description' => 'Cool description',
            'microfrontend_url' => 'https://front.example.com',
            'icon' => 'https://picsum.photos/200',
            'uninstall_token' => $uninstallToken,
        ]);

        $app = App::where('name', 'App name')->firstOrFail();

        $this->assertTrue($app->hasAllPermissions([
            'auth.login',
            'auth.identity_profile',
            'products.show',
            'products.add',
        ]));
    }

    public function testInstallAssignUnownedPermissions(): void
    {
        $this->user->givePermissionTo([
            'apps.install',
        ]);

        $response = $this->actingAs($this->user)->postJson('/apps', [
            'url' => $this->url,
            'allowed_permissions' => [
                'products.show',
            ],
        ]);

        $response->assertStatus(422);
        $this->assertDatabaseCount('apps', 0);
    }

    public function testInstallAssignInvalidPermissions(): void
    {
        $this->user->givePermissionTo([
            'apps.install',
        ]);

        $response = $this->actingAs($this->user)->postJson('/apps', [
            'url' => $this->url,
            'allowed_permissions' => [
                'nonexistent.permission',
            ],
        ]);

        $response->assertStatus(422);
        $this->assertDatabaseCount('apps', 0);
    }

    public function testInstallAppWantsInvalidPermissions(): void
    {
        $this->user->givePermissionTo([
            'apps.install',
            'products.show',
        ]);

        Http::fake([
            $this->url => Http::response([
                'name' => 'App name',
                'author' => 'Mr. Author',
                'version' => '1.0.0',
                'api_version' => '^1.4.0', // '^1.2.0' [TODO]
                'description' => 'Cool description',
                'microfrontend_url' => 'https://front.example.com',
                'icon' => 'https://picsum.photos/200',
                'licence_required' => false,
                'required_permissions' => [
                    'nonexistent.permission',
                ],
                'internal_permissions' => [[
                    'name' => 'product_layout',
                    'description' => 'Setup layouts of products page',
                ]],
            ]),
        ]);

        $response = $this->actingAs($this->user)->postJson('/apps', [
            'url' => $this->url,
            'allowed_permissions' => [
                'products.show',
            ],
        ]);

        $response->assertStatus(422);
        $this->assertDatabaseCount('apps', 0);
    }

    public function testInstallNotAssigningRequiredPermissions(): void
    {
        $this->user->givePermissionTo([
            'apps.install',
            'products.show',
        ]);

        Http::fake([
            $this->url => Http::response([
                'name' => 'App name',
                'author' => 'Mr. Author',
                'version' => '1.0.0',
                'api_version' => '^1.4.0', // '^1.2.0' [TODO]
                'description' => 'Cool description',
                'microfrontend_url' => 'https://front.example.com',
                'icon' => 'https://picsum.photos/200',
                'licence_required' => false,
                'required_permissions' => [
                    'products.show',
                ],
                'internal_permissions' => [[
                    'name' => 'product_layout',
                    'description' => 'Setup layouts of products page',
                ]],
            ]),
        ]);

        $response = $this->actingAs($this->user)->postJson('/apps', [
            'url' => $this->url,
            'allowed_permissions' => [],
        ]);

        $response->assertStatus(422);
        $this->assertDatabaseCount('apps', 0);
    }

    public function testInstallExtraPermissions(): void
    {
        $this->user->givePermissionTo([
            'apps.install',
            'products.show',
            'products.add',
            'products.edit',
        ]);

        Http::fake([
            $this->url => Http::response([
                'name' => 'App name',
                'author' => 'Mr. Author',
                'version' => '1.0.0',
                'api_version' => '^1.4.0', // '^1.2.0' [TODO]
                'description' => 'Cool description',
                'microfrontend_url' => 'https://front.example.com',
                'icon' => 'https://picsum.photos/200',
                'licence_required' => false,
                'required_permissions' => [
                    'products.show',
                ],
                'optional_permissions' => [
                    'products.add',
                ],
                'internal_permissions' => [[
                    'name' => 'product_layout',
                    'description' => 'Setup layouts of products page',
                ]],
            ]),
        ]);

        $response = $this->actingAs($this->user)->postJson('/apps', [
            'url' => $this->url,
            'allowed_permissions' => [
                'products.show',
                'products.add',
                'products.edit',
            ],
        ]);

        $response->assertStatus(422);
        $this->assertDatabaseCount('apps', 0);
    }

    public function testInstallConnectionRefusedRoot(): void
    {
        $this->user->givePermissionTo([
            'apps.install',
            'products.show',
            'products.add',
            'products.edit',
        ]);

        Http::fake([
            $this->url => new ConnectionException("Test", 7),
        ]);

        $response = $this->actingAs($this->user)->postJson('/apps', [
            'url' => $this->url,
            'allowed_permissions' => [
                'products.show',
                'products.add',
                'products.edit',
            ],
        ]);

        $response->assertStatus(422);
        $this->assertDatabaseCount('apps', 0);
    }

    public function testInstallConnectionRefusedInstall(): void
    {
        $this->user->givePermissionTo([
            'apps.install',
            'products.show',
            'products.add',
            'products.edit',
        ]);

        Http::retry(0);

        Http::fake([
            $this->url => Http::response([
                'name' => 'App name',
                'author' => 'Mr. Author',
                'version' => '1.0.0',
                'api_version' => '^1.4.0', // '^1.2.0' [TODO]
                'description' => 'Cool description',
                'microfrontend_url' => 'https://front.example.com',
                'icon' => 'https://picsum.photos/200',
                'licence_required' => false,
                'required_permissions' => [
                    'products.show',
                ],
                'internal_permissions' => [[
                    'name' => 'product_layout',
                    'description' => 'Setup layouts of products page',
                ]],
            ]),
            $this->url . '/install' => new ConnectionException("Test", 7),
        ]);

        $response = $this->actingAs($this->user)->postJson('/apps', [
            'url' => $this->url,
            'allowed_permissions' => [
                'products.show',
                'products.add',
                'products.edit',
            ],
        ]);

        $response->assertStatus(422);
        $this->assertDatabaseCount('apps', 0);
    }
}
