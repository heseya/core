<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Brand;
use App\Models\Product;
use App\Models\Category;
use Laravel\Passport\Passport;

class BrandsTest extends TestCase
{
    private Brand $brand;
    private Brand $brand_hidden;

    private array $expected;

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
            'id' => $this->brand->getKey(),
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
        $response = $this->getJson('/brands');
        $response
            ->assertOk()
            ->assertJsonCount(1, 'data') // Should show only public brands.
            ->assertJson(['data' => [
                0 => $this->expected,
            ]]);
    }

    /**
     * @return void
     */
    public function testCreate()
    {
        $response = $this->postJson('/brands');
        $response->assertUnauthorized();

        Passport::actingAs($this->user);

        $brand = [
            'name' => 'Test',
            'slug' => 'test-test',
            'public' => true,
        ];

        $response = $this->postJson('/brands', $brand);
        $response
            ->assertCreated()
            ->assertJson(['data' => $brand]);
    }

    /**
     * @return void
     */
    public function testUpdate()
    {
        $response = $this->patchJson('/brands/id:' . $this->brand->getKey());
        $response->assertUnauthorized();

        Passport::actingAs($this->user);

        $brand = [
            'name' => 'Test 2',
            'slug' => 'test-2',
            'public' => false,
        ];

        $response = $this->patchJson(
            '/brands/id:' . $this->brand->getKey(),
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
        $response = $this->deleteJson('/brands/id:' . $this->brand->getKey());
        $response->assertUnauthorized();

        Passport::actingAs($this->user);

        $response = $this->deleteJson('/brands/id:' . $this->brand->getKey());
        $response->assertNoContent();
    }

    /**
     * @return void
     */
    public function testDeleteWithRelations()
    {
        Passport::actingAs($this->user);

        $this->brand = factory(Brand::class)->create();
        $category = factory(Category::class)->create();

        factory(Product::class)->create([
            'brand_id' => $this->brand->getKey(),
            'category_id' => $category->getKey(),
        ]);

        $response = $this->deleteJson('/brands/id:' . $this->brand->getKey());
        $response->assertStatus(409);
    }
}
