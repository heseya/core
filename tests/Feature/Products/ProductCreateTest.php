<?php

namespace Tests\Feature\Products;

use App\Models\Page;
use App\Models\Product;
use Tests\TestCase;

class ProductCreateTest extends TestCase
{
    /**
     * @dataProvider authProvider
     */
    public function testCreateDescriptions(string $user): void
    {
        $page = Page::factory()->create();

        $this->{$user}->givePermissionTo('products.add');
        $response = $this
            ->actingAs($this->{$user})
            ->json('POST', '/products', [
                'name' => 'Test',
                'slug' => 'slug',
                'price' => 100,
                'public' => true,
                'shipping_digital' => false,
                'descriptions' => [$page->getKey()],
            ])
            ->assertCreated()
            ->assertJsonPath('data.descriptions.0.id', $page->getKey());

        $this->assertDatabaseHas('product_page', [
            'product_id' => $response->json('data.id'),
            'page_id' => $page->getKey(),
        ]);
    }

    /**
     * @dataProvider authProvider
     */
    public function testUpdateDescriptions(string $user): void
    {
        $product = Product::factory()->create();
        $page = Page::factory()->create();

        $this->{$user}->givePermissionTo('products.edit');
        $response = $this
            ->actingAs($this->{$user})
            ->json('PATCH', "/products/id:{$product->getKey()}", [
                'descriptions' => [$page->getKey()],
            ])
            ->assertOk()
            ->assertJsonPath('data.descriptions.0.id', $page->getKey());

        $this->assertDatabaseHas('product_page', [
            'product_id' => $response->json('data.id'),
            'page_id' => $page->getKey(),
        ]);
    }
}
