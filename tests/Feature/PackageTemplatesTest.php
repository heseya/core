<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\PackageTemplate;
use Laravel\Passport\Passport;

class PackageTemplatesTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();

        $this->package = factory(PackageTemplate::class)->create();

        /**
         * Expected response
         */
        $this->expected = [
            'id' => $this->package->id,
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
        $response = $this->post('/package-templates');
        $response->assertUnauthorized();

        Passport::actingAs($this->user);

        $response = $this->get('/package-templates');
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
        $response = $this->post('/package-templates');
        $response->assertUnauthorized();

        Passport::actingAs($this->user);

        $package = [
            'name' => 'Small package',
            'weight' => 1.2,
            'width' => 10,
            'height' => 6,
            'depth' => 2,
        ];

        $response = $this->post('/package-templates', $package);
        $response
            ->assertCreated()
            ->assertJson(['data' => $package]);
    }

    /**
     * @return void
     */
    public function testUpdate()
    {
        $response = $this->patch('/package-templates/id:' . $this->package->id);
        $response->assertUnauthorized();

        Passport::actingAs($this->user);

        $package = [
            'name' => 'PackageTemplate big',
            'weight' => 5.7,
            'width' => 50,
            'height' => 30,
            'depth' => 20,
        ];

        $response = $this->patch(
            '/package-templates/id:' . $this->package->id,
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
        $response = $this->delete('/package-templates/id:' . $this->package->id);
        $response->assertUnauthorized();

        Passport::actingAs($this->user);

        $response = $this->delete('/package-templates/id:' . $this->package->id);
        $response->assertNoContent();
    }
}
