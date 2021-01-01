<?php

namespace Tests\Feature;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use Laravel\Passport\Passport;
use Tests\TestCase;

class BrandTest extends TestCase
{
    private Brand $brand;
    private Brand $brand_hidden;

    private array $expected;

    public function setUp(): void
    {
        parent::setUp();

        $this->brand = Brand::factory()->create([
            'public' => true,
        ]);

        $this->brand_hidden = Brand::factory()->create([
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

        $this->assertDatabaseHas('brands', $brand);
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

        $this->assertDatabaseHas('brands', $brand + ['id' => $this->brand->getKey()]);
    }

    /**
     * @return void
     */
    public function testDelete()
    {
        $response = $this->deleteJson('/brands/id:' . $this->brand->getKey());
        $response->assertUnauthorized();
        $this->assertDatabaseHas('brands', $this->brand->toArray());

        Passport::actingAs($this->user);

        $response = $this->deleteJson('/brands/id:' . $this->brand->getKey());
        $response->assertNoContent();
        $this->assertDeleted($this->brand);
    }

    /**
     * @return void
     */
    public function testDeleteWithRelations()
    {
        Passport::actingAs($this->user);

        $this->brand = Brand::factory()->create();
        $category = Category::factory()->create();

        Product::factory()->create([
            'brand_id' => $this->brand->getKey(),
            'category_id' => $category->getKey(),
        ]);

        $response = $this->deleteJson('/brands/id:' . $this->brand->getKey());
        $response->assertStatus(409);
        $this->assertDatabaseHas('brands', $this->brand->toArray());
    }
}
