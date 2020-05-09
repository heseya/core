<?php

namespace Tests\Feature;

use App\Brand;
use App\Product;
use App\Category;
use Tests\TestCase;
use Laravel\Passport\Passport;
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
            ->assertOk()
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
        $response = $this->post('/brands');
        $response->assertUnauthorized();

        Passport::actingAs($this->user);

        $brand = [
            'name' => 'Test',
            'slug' => 'test-test',
            'public' => true,
        ];

        $response = $this->post('/brands', $brand);

        $response
            ->assertCreated()
            ->assertJson(['data' => $brand]);
    }

    /**
     * @return void
     */
    public function testUpdate()
    {
        $response = $this->patch('/brands/id:' . $this->brand->id);
        $response->assertUnauthorized();

        Passport::actingAs($this->user);

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
            ->assertOk()
            ->assertJson(['data' => $brand]);
    }

    /**
     * @return void
     */
    public function testDelete()
    {
        $response = $this->delete('/brands/id:' . $this->brand->id);
        $response->assertUnauthorized();

        Passport::actingAs($this->user);

        $response = $this->delete('/brands/id:' . $this->brand->id);

        $response->assertNoContent();
    }

    /**
     * @return void
     */
    public function testDeleteWithRelations()
    {
        $this->brand = factory(Brand::class)->create();
        $this->category = factory(Category::class)->create();
        factory(Product::class)->create([
            'brand_id' => $this->brand->id,
            'category_id' => $this->category->id,
        ]);

        $response = $this->delete('/brands/id:' . $this->brand->id);

        $response->assertStatus(400);
    }
}
