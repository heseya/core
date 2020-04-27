<?php

namespace Tests\Feature;

use App\Category;
use Tests\TestCase;
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
            ->assertStatus(200)
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
        $category = [
            'name' => 'Test',
            'slug' => 'test-test',
            'public' => true,
        ];

        $response = $this->post('/categories', $category);

        $response
            ->assertStatus(201)
            ->assertJson(['data' => $category]);
    }

    /**
     * @return void
     */
    public function testUpdate()
    {
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
            ->assertStatus(200)
            ->assertJson(['data' => $category]);
    }

    /**
     * @return void
     */
    public function testDelete()
    {
        $response = $this->delete('/categories/id:' . $this->category->id);

        $response->assertStatus(204);
    }
}
