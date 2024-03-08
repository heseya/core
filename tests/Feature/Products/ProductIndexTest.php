<?php

namespace Tests\Feature\Products;

use App\Models\Product;
use Domain\ProductSet\ProductSet;

class ProductIndexTest extends ProductTestCase
{
    /**
     * @dataProvider authProvider
     */
    public function testIndexWithTranslationsFlag(string $user): void
    {
        $this->{$user}->givePermissionTo('products.show');

        $response = $this->actingAs($this->{$user})->getJson('/products?limit=100&with_translations=1');
        $response
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJson([
                'data' => [
                    $this->expected_short,
                ],
            ])
            ->assertJsonFragment([
                [
                    'net' => '100.00',
                    'gross' => '100.00',
                    'currency' => 'PLN',
                ],
            ]);

        $this->assertArrayHasKey('translations', $response->json('data.0'));
        $this->assertIsArray($response->json('data.0.translations'));
        $this->assertQueryCountLessThan(26);
    }

    public function testIndexUnauthorized(): void
    {
        $this->getJson('/products')->assertForbidden();
    }

    /**
     * @dataProvider authProvider
     */
    public function testIndex(string $user): void
    {
        $this->{$user}->givePermissionTo('products.show');

        $product = Product::factory()->create([
            'public' => true,
        ]);
        $set = ProductSet::factory()->create([
            'public' => true,
        ]);
        $product->sets()->sync([$set->getKey()]);

        $this
            ->actingAs($this->{$user})
            ->json('GET', '/products', ['limit' => 100])
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonFragment([
                ...$this->expected_short,
                [
                    'net' => '100.00',
                    'gross' => '100.00',
                    'currency' => 'PLN',
                ],
            ]);

        $this->assertQueryCountLessThan(29);
    }

    /**
     * @dataProvider authProvider
     */
    public function testIndexHidden(string $user): void
    {
        $this->{$user}->givePermissionTo(['products.show', 'products.show_hidden']);

        $product = Product::factory()->create([
            'public' => true,
        ]);
        $set = ProductSet::factory()->create([
            'public' => false,
        ]);

        $product->sets()->sync([$set->getKey()]);

        $this->actingAs($this->{$user})
            ->json('GET', '/products')
            ->assertOk()
            ->assertJsonCount(2, 'data'); // Should show all products.
    }
}
