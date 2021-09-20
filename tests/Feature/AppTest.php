<?php

namespace Tests\Feature;

use App\Models\App;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Tests\TestCase;

class AppTest extends TestCase
{
    use RefreshDatabase;

    private string $url = 'https://test.app.heseya';

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

//    public function testRegisterNotFound(): void
//    {
//        $this->user->givePermissionTo('apps.install');
//
//        Http::fake([
//            $this->url => Http::response([], 404),
//        ]);
//
//        $response = $this->actingAs($this->user)->postJson('/apps', [
//            'url' => $this->url,
//        ]);
//
//        $response->assertStatus(400);
//        $this->assertDatabaseCount('apps', 0);
//    }

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

        $response->assertCreated();
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
    }
}
