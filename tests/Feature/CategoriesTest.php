<?php

namespace Tests\Feature;

use App\Models\Brand;
use App\Models\Product;
use App\Models\Category;
use Tests\TestCase;
use Laravel\Passport\Passport;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;

class CategoriesTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();

        $this->category = factory(Category::class)->create([
            'public' => true,
        ]);

        $this->category_hidden = factory(Category::class)->create([
            'public' => false,
        ]);

        /**
         * Expected response
         */
        $this->expected = [
            'id' => $this->category->id,
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
        $response = $this->get('/categories');
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
        $response = $this->post('/categories');
        $response->assertUnauthorized();

        Passport::actingAs($this->user);

        $category = [
            'name' => 'Test',
            'slug' => 'test-test',
            'public' => true,
        ];

        $response = $this->post('/categories', $category);
        $response
            ->assertCreated()
            ->assertJson(['data' => $category]);
    }

    /**
     * @return void
     */
    public function testUpdate()
    {
        $response = $this->patch('/categories/id:' . $this->category->id);
        $response->assertUnauthorized();

        Passport::actingAs($this->user);

        $category = [
            'name' => 'Test 2',
            'slug' => 'test-2',
            'public' => false,
        ];

        $response = $this->patch(
            '/categories/id:' . $this->category->id,
            $category,
        );
        $response
            ->assertOk()
            ->assertJson(['data' => $category]);
    }

    /**
     * @return void
     */
    public function testDelete()
    {
        $response = $this->delete('/categories/id:' . $this->category->id);
        $response->assertUnauthorized();

        Passport::actingAs($this->user);

        $response = $this->delete('/categories/id:' . $this->category->id);
        $response->assertNoContent();
    }

    /**
     * @return void
     */
    public function testDeleteWithRelations()
    {
        Passport::actingAs($this->user);

        $this->category = factory(Category::class)->create();
        $this->brand = factory(Brand::class)->create();

        factory(Product::class)->create([
            'category_id' => $this->category->id,
            'brand_id' => $this->brand->id,
        ]);

        $response = $this->delete('/categories/id:' . $this->category->id);
        $response->assertStatus(400);
    }
}
