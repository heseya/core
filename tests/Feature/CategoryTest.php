<?php

namespace Tests\Feature;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use Laravel\Passport\Passport;
use Tests\TestCase;

class CategoryTest extends TestCase
{
    private Category $category;
    private Category $category_hidden;

    private array $expected;

    public function setUp(): void
    {
        parent::setUp();

        $this->category = Category::factory()->create([
            'public' => true,
        ]);

        $this->category_hidden = Category::factory()->create([
            'public' => false,
        ]);

        /**
         * Expected response
         */
        $this->expected = [
            'id' => $this->category->getKey(),
            'name' => $this->category->name,
            'slug' => $this->category->slug,
            'public' => $this->category->public,
        ];
    }

    /**
     * @return void
     */
    public function testIndex()
    {
        $response = $this->getJson('/categories');
        $response
            ->assertOk()
            ->assertJsonCount(1, 'data') // Shoud show only public categories.
            ->assertJson(['data' => [
                0 => $this->expected,
            ]]);
    }

    /**
     * @return void
     */
    public function testCreate()
    {
        $response = $this->postJson('/categories');
        $response->assertUnauthorized();

        Passport::actingAs($this->user);

        $category = [
            'name' => 'Test',
            'slug' => 'test-test',
            'public' => true,
        ];

        $response = $this->postJson('/categories', $category);
        $response
            ->assertCreated()
            ->assertJson(['data' => $category]);

        $this->assertDatabaseHas('categories', $category);
    }

    /**
     * @return void
     */
    public function testUpdate()
    {
        $response = $this->patchJson('/categories/id:' . $this->category->getKey());
        $response->assertUnauthorized();

        Passport::actingAs($this->user);

        $category = [
            'name' => 'Test 2',
            'slug' => 'test-2',
            'public' => false,
        ];

        $response = $this->patchJson(
            '/categories/id:' . $this->category->getKey(),
            $category,
        );
        $response
            ->assertOk()
            ->assertJson(['data' => $category]);

        $this->assertDatabaseHas('categories', $category + ['id' => $this->category->getKey()]);
    }

    /**
     * @return void
     */
    public function testDelete()
    {
        $response = $this->deleteJson('/categories/id:' . $this->category->getKey());
        $response->assertUnauthorized();
        $this->assertDatabaseHas('categories', $this->category->toArray());

        Passport::actingAs($this->user);

        $response = $this->deleteJson('/categories/id:' . $this->category->getKey());
        $response->assertNoContent();
        $this->assertDeleted($this->category);
    }

    /**
     * @return void
     */
    public function testDeleteWithRelations()
    {
        Passport::actingAs($this->user);

        $this->category = Category::factory()->create();
        $brand = Brand::factory()->create();

        Product::factory()->create([
            'category_id' => $this->category->getKey(),
            'brand_id' => $brand->getKey(),
        ]);

        $response = $this->delete('/categories/id:' . $this->category->getKey());
        $response->assertStatus(409);
        $this->assertDatabaseHas('categories', $this->category->toArray());
    }
}
