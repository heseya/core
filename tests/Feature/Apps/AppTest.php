<?php

namespace Tests\Feature\Apps;

use App\Models\App;
use Illuminate\Foundation\Testing\RefreshDatabase;
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

        App::factory()->count(9)->create(); // +1 from TestCase

        $response = $this->actingAs($this->user)->getJson('/apps');

        $response
            ->assertOk()
            ->assertJsonCount(10, 'data');
    }

    public function testIndexSearchByIds(): void
    {
        $this->user->givePermissionTo('apps.show');

        App::factory()->count(9)->create(); // +1 from TestCase

        $response = $this->actingAs($this->user)->json('GET', '/apps', [
            'ids' => [
                $this->application->getKey(),
            ],
        ]);

        $response
            ->assertOk()
            ->assertJsonCount(1, 'data');
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
            ->assertJson([
                'data' => [
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
}
