<?php

namespace Tests\Feature;

use App\Models\PackageTemplate;
use Laravel\Passport\Passport;
use Tests\TestCase;

class PackageTemplatesTest extends TestCase
{
    private PackageTemplate $package;

    private array $expected;

    public function setUp(): void
    {
        parent::setUp();

        $this->package = PackageTemplate::factory()->create();

        /**
         * Expected response
         */
        $this->expected = [
            'id' => $this->package->getKey(),
            'name' => $this->package->name,
            'weight' => $this->package->weight,
            'width' => $this->package->width,
            'height' => $this->package->height,
            'depth' => $this->package->depth,
        ];
    }

    /**
     * @return void
     */
    public function testIndex()
    {
        $response = $this->postJson('/package-templates');
        $response->assertUnauthorized();

        Passport::actingAs($this->user);

        $response = $this->getJson('/package-templates');
        $response
            ->assertOk()
            ->assertJson(['data' => [
                0 => $this->expected,
            ]]);
    }

    /**
     * @return void
     */
    public function testCreate()
    {
        $response = $this->postJson('/package-templates');
        $response->assertUnauthorized();

        Passport::actingAs($this->user);

        $package = [
            'name' => 'Small package',
            'weight' => 1.2,
            'width' => 10,
            'height' => 6,
            'depth' => 2,
        ];

        $response = $this->postJson('/package-templates', $package);
        $response
            ->assertCreated()
            ->assertJson(['data' => $package]);
    }

    /**
     * @return void
     */
    public function testUpdate()
    {
        $response = $this->patchJson('/package-templates/id:' . $this->package->getKey());
        $response->assertUnauthorized();

        Passport::actingAs($this->user);

        $package = [
            'name' => 'PackageTemplate big',
            'weight' => 5.7,
            'width' => 50,
            'height' => 30,
            'depth' => 20,
        ];

        $response = $this->patchJson(
            '/package-templates/id:' . $this->package->getKey(),
            $package,
        );

        $response
            ->assertOk()
            ->assertJson(['data' => $package]);
    }

    /**
     * @return void
     */
    public function testDelete()
    {
        $response = $this->deleteJson('/package-templates/id:' . $this->package->getKey());
        $response->assertUnauthorized();

        Passport::actingAs($this->user);

        $response = $this->deleteJson('/package-templates/id:' . $this->package->getKey());
        $response->assertNoContent();
    }
}
