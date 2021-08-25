<?php

namespace Tests\Feature;

use App\Models\ProductSet;
use Tests\TestCase;

class BrandTest extends TestCase
{
    private ProductSet $brand;
    private ProductSet $brand_hidden;

    public function setUp(): void
    {
        parent::setUp();

        $brands = ProductSet::factory()->create([
            'name' => 'Brands',
            'slug' => 'brands',
            'public' => true,
            'public_parent' => true,
        ]);

        $this->brand = ProductSet::factory()->create([
            'public' => true,
            'public_parent' => true,
            'parent_id' => $brands->getKey(),
            'order' => 0,
        ]);

        $this->brand_hidden = ProductSet::factory()->create([
            'public' => false,
            'public_parent' => true,
            'parent_id' => $brands->getKey(),
            'order' => 1,
        ]);
    }

    public function testIndexUnauthorized(): void
    {
        $response = $this->getJson('/brands');
        $response->assertForbidden();
    }

    public function testIndex(): void
    {
        $this->user->givePermissionTo('product_sets.show');

        $response = $this->actingAs($this->user)->getJson('/brands');
        $response
            ->assertOk()
            ->assertJsonCount(1, 'data') // Should show only public brands.
            ->assertJson(['data' => [
                0 => [
                    'id' => $this->brand->getKey(),
                    'name' => $this->brand->name,
                    'slug' => $this->brand->slug,
                    'public' => $this->brand->public,
                ],
            ]]);
    }

    public function testIndexHidden(): void
    {
        $this->user->givePermissionTo([
            'product_sets.show',
            'product_sets.show_hidden',
        ]);

        $response = $this->actingAs($this->user)->getJson('/brands');
        $response
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJson(['data' => [
                0 => [
                    'id' => $this->brand->getKey(),
                    'name' => $this->brand->name,
                    'slug' => $this->brand->slug,
                    'public' => $this->brand->public,
                ],
                1 => [
                    'id' => $this->brand_hidden->getKey(),
                    'name' => $this->brand_hidden->name,
                    'slug' => $this->brand_hidden->slug,
                    'public' => $this->brand_hidden->public,
                ],
            ]]);
    }
}
