<?php

namespace Tests\Feature;

use App\Models\App;
use App\Models\Permission;
use App\Models\Role;
use App\Models\ShippingMethod;
use App\Models\WebHook;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class AppOtherTest extends TestCase
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

        App::factory()->count(9)->create(); // +1 from TestCase

        $response = $this->actingAs($this->user)->getJson('/apps');

        $response
            ->assertOk()
            ->assertJsonCount(10, 'data');
    }

    public function testShowUnauthorized(): void
    {
        $app = App::factory()->create();

        $response = $this->getJson('/apps/id:' . $app->getKey());

        $response->assertForbidden();
    }

    public function testShow(): void
    {
        $this->user->givePermissionTo('apps.show_details');

        $app = App::factory()->create();

        $response = $this->actingAs($this->user)
            ->getJson('/apps/id:' . $app->getKey());

        $response
            ->assertOk()
            ->assertJson(['data' => [
                'url' => $app->url,
                'microfrontend_url' => $app->microfrontend_url,
                'name' => $app->name,
                'slug' => $app->slug,
                'version' => $app->version,
                'description' => $app->description,
                'icon' => $app->icon,
                'author' => $app->author,
                'permissions' => [],
                'metadata' => [],
            ],
            ]);
    }

    public function testShowWrongId(): void
    {
        $this->user->givePermissionTo('apps.show_details');

        $app = App::factory()->create();

        $this->actingAs($this->user)
            ->getJson('/apps/id:its-not-uuid')
            ->assertNotFound();

        $this->actingAs($this->user)
            ->getJson('/apps/id:' . $app->getKey() . $app->getKey())
            ->assertNotFound();
    }

    public function testUninstallUnauthorized(): void
    {
        $app = App::factory()->create(['url' => $this->url]);

        $response = $this->deleteJson('/apps/id:' . $app->getKey());

        $response->assertForbidden();
    }

    public function testUninstallNotFound(): void
    {
        $this->user->givePermissionTo('apps.remove');

        $app = App::factory()->create(['url' => $this->url]);

        Http::fake([
            $this->url . '/uninstall' => Http::response([], 404),
        ]);

        $response = $this->actingAs($this->user)
            ->deleteJson('/apps/id:' . $app->getKey());

        $response->assertStatus(422);
        $this->assertDatabaseCount('apps', 2);  // +1 from TestCase
    }

    public function testUninstallNotFoundForce(): void
    {
        $this->user->givePermissionTo('apps.remove');

        $app = App::factory()->create(['url' => $this->url]);

        Http::fake([
            $this->url . '/uninstall' => Http::response([], 404),
        ]);

        $response = $this->actingAs($this->user)
            ->json('DELETE', '/apps/id:' . $app->getKey(), ['force' => true]);

        $response->assertNoContent();
        $this->assertDatabaseCount('apps', 1); // +1 from TestCase
        $this->assertModelMissing($app);
    }

    public function testUninstallConnectionRefused(): void
    {
        $this->user->givePermissionTo('apps.remove');

        $app = App::factory()->create(['url' => $this->url]);

        Http::fake([
            $this->url . '/uninstall' => new ConnectionException('Test', 7),
        ]);

        $response = $this->actingAs($this->user)
            ->deleteJson('/apps/id:' . $app->getKey() . '?force=0');

        $response->assertStatus(422);
        $this->assertDatabaseCount('apps', 2); // +1 from TestCase
    }

    public function testUninstallConnectionRefusedForce(): void
    {
        $this->user->givePermissionTo('apps.remove');

        $app = App::factory()->create(['url' => $this->url]);

        Http::fake([
            $this->url . '/uninstall' => new ConnectionException('Test', 7),
        ]);

        $response = $this->actingAs($this->user)
            ->deleteJson('/apps/id:' . $app->getKey() . '?force=1');

        $response->assertNoContent();
        $this->assertDatabaseCount('apps', 1); // +1 from TestCase
        $this->assertModelMissing($app);
    }

    public function testUninstall(): void
    {
        $this->user->givePermissionTo('apps.remove');

        $app = App::factory()->create(['url' => $this->url]);

        Http::fake([
            $this->url . '/uninstall' => Http::response(status: 204),
        ]);

        $response = $this->actingAs($this->user)
            ->deleteJson('/apps/id:' . $app->getKey());

        $response->assertNoContent();
        $this->assertDatabaseCount('apps', 1); // +1 from TestCase
        $this->assertModelMissing($app);
    }

    public function shortBooleanProvider(): array
    {
        return [
            'as true' => [true],
            'as false' => [false],
            'as 1' => [1],
            'as 0' => [0],
            'as on' => ['on'],
            'as off' => ['off'],
            'as yes' => ['yes'],
            'as no' => ['no'],
        ];
    }

    /**
     * @dataProvider shortBooleanProvider
     */
    public function testUninstallForce($boolean): void
    {
        $this->user->givePermissionTo('apps.remove');

        $app = App::factory()->create(['url' => $this->url]);

        Http::fake([
            $this->url . '/uninstall' => Http::response(status: 204),
        ]);

        $response = $this->actingAs($this->user)
            ->json('DELETE', '/apps/id:' . $app->getKey(), ['force' => $boolean]);

        $response->assertNoContent();
        $this->assertDatabaseCount('apps', 1); // +1 from TestCase
        $this->assertModelMissing($app);
    }

    public function testUninstallRole(): void
    {
        $this->user->givePermissionTo('apps.remove');

        $role = Role::create(['name' => 'Appname owner']);

        $app = App::factory()->create([
            'slug' => 'appname',
            'url' => $this->url,
            'role_id' => $role->getKey(),
        ]);

        $permission = Permission::create(['name' => 'app.appname.permission']);
        $role->givePermissionTo($permission);
        $this->user->assignRole($role);

        Http::fake([
            $this->url . '/uninstall' => Http::response(status: 204),
        ]);

        $response = $this->actingAs($this->user)
            ->deleteJson('/apps/id:' . $app->getKey());

        $response->assertNoContent();
        $this->assertDatabaseCount('apps', 1); // +1 from TestCase

        $this->assertModelMissing($app);
        $this->assertModelMissing($role);
        $this->assertModelMissing($permission);

        $this->user->refresh();
        $this->assertFalse($this->user->hasRole($role));
        $this->assertFalse($this->user->hasPermissionTo($permission));
    }

    public function testUninstallWebHooks(): void
    {
        $this->user->givePermissionTo('apps.remove');

        $app = App::factory()->create(['url' => $this->url]);

        $webhook = WebHook::factory([
            'model_type' => $app::class,
            'creator_id' => $app->getKey(),
        ])->create();

        $this->assertDatabaseHas('web_hooks', [
            'creator_id' => $app->getKey(),
            'model_type' => App::class,
        ]);

        $this->assertTrue($app->webhooks->isNotEmpty());

        Http::fake([
            $this->url . '/uninstall' => Http::response(status: 204),
        ]);

        $response = $this->actingAs($this->user)
            ->json('DELETE', '/apps/id:' . $app->getKey());

        $response->assertNoContent();
        $this->assertDatabaseCount('apps', 1); // +1 from TestCase
        $this->assertModelMissing($app);
        $this->assertSoftDeleted($webhook);
    }

    public function testUninstallCommand(): void
    {
        $app = App::factory()->create(['url' => $this->url]);

        Http::fake([
            $this->url . '/uninstall' => Http::response(status: 204),
            $this->application->url . '/uninstall' => Http::response(status: 204),
        ]);

        $this->artisan('apps:remove')->assertExitCode(0);

        $this->assertDatabaseCount('apps', 0); // +1 from TestCase
        $this->assertModelMissing($app);
        $this->assertModelMissing($this->application);
    }

    public function testRemoveIntegrationsWithApp(): void
    {
        $this->user->givePermissionTo('apps.remove');

        ShippingMethod::query()->delete();
        App::query()->delete();

        $app = App::factory()->create(['url' => $this->url]);
        $shippingMethodOne = ShippingMethod::factory()->create(['app_id' => $app->getKey()]);
        $shippingMethodTwo = ShippingMethod::factory()->create(['app_id' => $app->getKey()]);
        $shippingMethodIdOne = $shippingMethodOne->getKey();
        $shippingMethodIdTwo = $shippingMethodTwo->getKey();

        $this->actingAs($this->user)
            ->deleteJson('/apps/id:' . $app->getKey() . '?force');

        $this->assertDatabaseMissing('shipping_methods', [
            'id' => $shippingMethodIdOne,
        ]);
        $this->assertDatabaseMissing('shipping_methods', [
            'id' => $shippingMethodIdTwo,
        ]);

        $this->assertDatabaseCount('apps', 0);
        $this->assertDatabaseCount('shipping_methods', 0);
        $this->assertModelMissing($app);
        $this->assertModelMissing($shippingMethodOne);
        $this->assertModelMissing($shippingMethodTwo);
    }
}
