<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Package;
use Laravel\Passport\Passport;

class PackagesTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();

        $this->package = factory(Package::class)->create();

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
        $response = $this->post('/packages');
        $response->assertUnauthorized();

        Passport::actingAs($this->user);

        $response = $this->get('/packages');
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
        $response = $this->post('/packages');
        $response->assertUnauthorized();

        Passport::actingAs($this->user);

        $package = [
            'name' => 'Small package',
            'weight' => 1.2,
            'width' => 10,
            'height' => 6,
            'depth' => 2,
        ];

        $response = $this->post('/packages', $package);
        $response
            ->assertCreated()
            ->assertJson(['data' => $package]);
    }

    /**
     * @return void
     */
    public function testUpdate()
    {
        $response = $this->patch('/packages/id:' . $this->package->id);
        $response->assertUnauthorized();

        Passport::actingAs($this->user);

        $package = [
            'name' => 'Package big',
            'weight' => 5.7,
            'width' => 50,
            'height' => 30,
            'depth' => 20,
        ];

        $response = $this->patch(
            '/packages/id:' . $this->package->id,
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
        $response = $this->delete('/packages/id:' . $this->package->id);
        $response->assertUnauthorized();

        Passport::actingAs($this->user);

        $response = $this->delete('/packages/id:' . $this->package->id);
        $response->assertNoContent();
    }
}
