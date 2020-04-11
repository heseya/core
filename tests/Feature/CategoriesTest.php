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
}
