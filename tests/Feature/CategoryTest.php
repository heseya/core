<?php

namespace Tests\Feature;

use App\Models\ProductSet;
use Tests\TestCase;

class CategoryTest extends TestCase
{
    private ProductSet $category;
    private ProductSet $category_hidden;

    public function setUp(): void
    {
        parent::setUp();

        $categories = ProductSet::factory()->create([
            'name' => 'categories',
            'slug' => 'categories',
            'public' => true,
            'public_parent' => true,
        ]);

        $this->category = ProductSet::factory()->create([
            'public' => true,
            'public_parent' => true,
            'parent_id' => $categories->getKey(),
            'order' => 0,
        ]);

        $this->category_hidden = ProductSet::factory()->create([
            'public' => false,
            'public_parent' => true,
            'parent_id' => $categories->getKey(),
            'order' => 1,
        ]);
    }

    public function testIndexUnauthorized(): void
    {
        $response = $this->getJson('/categories');
        $response->assertForbidden();
    }

    public function testIndex(): void
    {
        $this->user->givePermissionTo('product_sets.show');

        $response = $this->actingAs($this->user)->getJson('/categories');
        $response
            ->assertOk()
            ->assertJsonCount(1, 'data') // Should show only public categories.
            ->assertJson(['data' => [
                0 => [
                    'id' => $this->category->getKey(),
                    'name' => $this->category->name,
                    'slug' => $this->category->slug,
                    'public' => $this->category->public,
                ],
            ]]);
    }

    public function testIndexHidden(): void
    {
        $this->user->givePermissionTo([
            'product_sets.show',
            'product_sets.show_hidden',
        ]);

        $response = $this->actingAs($this->user)->getJson('/categories');
        $response
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJson(['data' => [
                0 => [
                    'id' => $this->category->getKey(),
                    'name' => $this->category->name,
                    'slug' => $this->category->slug,
                    'public' => $this->category->public,
                ],
                1 => [
                    'id' => $this->category_hidden->getKey(),
                    'name' => $this->category_hidden->name,
                    'slug' => $this->category_hidden->slug,
                    'public' => $this->category_hidden->public,
                ],
            ]]);
    }
}
