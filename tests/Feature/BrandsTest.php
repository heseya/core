<?php

namespace Tests\Feature;

use App\Brand;
use Tests\TestCase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;

class BrandsTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();

        $this->brand = factory(Brand::class)->create([
            'public' => true,
        ]);

        $this->brand_hidden = factory(Brand::class)->create([
            'public' => false,
        ]);

        /**
         * Expected response
         */
        $this->expected = [
            'id' => $this->brand->id,
            'name' => $this->brand->name,
            'slug' => $this->brand->slug,
            'public' => $this->brand->public,
        ];
    }

    /**
     * @return void
     */
    public function testIndex()
    {
        $response = $this->get('/brands');

        $response
            ->assertStatus(200)
            ->assertJsonCount(1, 'data') // Shoud show only public brands.
            ->assertJson(['data' => [
                0 => $this->expected,
            ]]);
    }

    /**
     * @return void
     */
    public function testCreate()
    {
        $brand = [
            'name' => 'Test',
            'slug' => 'test-test',
            'public' => true,
        ];

        $response = $this->post('/brands', $brand);

        $response
            ->assertStatus(201)
            ->assertJson(['data' => $brand]);
    }

    /**
     * @return void
     */
    public function testUpdate()
    {
        $brand = [
            'name' => 'Test 2',
            'slug' => 'test-2',
            'public' => false,
        ];

        $response = $this->patch(
            '/brands/id:' . $this->brand->id,
            $brand,
        );

        $response
            ->assertStatus(200)
            ->assertJson(['data' => $brand]);
    }

    /**
     * @return void
     */
    public function testDelete()
    {
        $response = $this->delete('/brands/id:' . $this->brand->id);

        $response->assertStatus(204);
    }
}
