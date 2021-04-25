<?php

namespace Tests\Feature;

use App\Models\App;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class AppTest extends TestCase
{
    use RefreshDatabase;

    private string $url = 'https://test.app.heseya';

    public function testIndexUnauthorized(): void
    {
        $response = $this->getJson('/apps');

        $response->assertUnauthorized();
    }

    public function testIndex(): void
    {
        App::factory()->count(10)->create();

        $response = $this->actingAs($this->user)->getJson('/apps');

        $response
            ->assertOk()
            ->assertJsonCount(10, 'data');
    }

    public function testRegisterUnauthorized(): void
    {
        $response = $this->postJson('/apps');

        $response->assertUnauthorized();
    }

    public function testRegisterNotFound(): void
    {
        Http::fake([
            $this->url => Http::response([], 404),
        ]);

        $response = $this->actingAs($this->user)->postJson('/apps', [
            'url' => $this->url,
        ]);

        $response->assertStatus(400);
        $this->assertDatabaseCount('apps', 0);
    }

    public function testRegister(): void
    {
        Http::fake([
            $this->url => Http::response([
                'name' => 'Test App',
            ]),
        ]);

        $response = $this->actingAs($this->user)->postJson('/apps', [
            'url' => $this->url,
        ]);

        $response->assertCreated();
        $this->assertDatabaseHas('apps', [
            'name' => 'Test App',
            'url' => $this->url,
        ]);
    }
}