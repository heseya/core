<?php

namespace Tests\Feature;

use App\Models\App;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
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

        App::factory()->count(10)->create();

        $response = $this->actingAs($this->user)->getJson('/apps');

        $response
            ->assertOk()
            ->assertJsonCount(10, 'data');
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
        $this->assertDatabaseCount('apps', 1);
    }

    public function testUninstallNotFoundForce(): void
    {
        $this->user->givePermissionTo('apps.remove');

        $app = App::factory()->create(['url' => $this->url]);

        Http::fake([
            $this->url . '/uninstall' => Http::response([], 404),
        ]);

        $response = $this->actingAs($this->user)
            ->deleteJson('/apps/id:' . $app->getKey() . '?force');

        $response->assertNoContent();
        $this->assertDatabaseCount('apps', 0);
    }

    public function testUninstallConnectionRefused(): void
    {
        $this->user->givePermissionTo('apps.remove');

        $app = App::factory()->create(['url' => $this->url]);

        Http::fake([
            $this->url . '/uninstall' => new ConnectionException("Test", 7),
        ]);

        $response = $this->actingAs($this->user)
            ->deleteJson('/apps/id:' . $app->getKey() . '?force=0');

        $response->assertStatus(422);
        $this->assertDatabaseCount('apps', 1);
    }

    public function testUninstallConnectionRefusedForce(): void
    {
        $this->user->givePermissionTo('apps.remove');

        $app = App::factory()->create(['url' => $this->url]);

        Http::fake([
            $this->url . '/uninstall' => new ConnectionException("Test", 7),
        ]);

        $response = $this->actingAs($this->user)
            ->deleteJson('/apps/id:' . $app->getKey() . '?force=1');

        $response->assertNoContent();
        $this->assertDatabaseCount('apps', 0);
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
        $this->assertDatabaseCount('apps', 0);
    }
}
