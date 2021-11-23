<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\ProductSet;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class ProductSearchTest extends TestCase
{
    /**
     * @dataProvider authProvider
     */
    public function testSearch($user): void
    {
        $this->$user->givePermissionTo('products.show');

        $product = Product::factory()->create([
            'public' => true,
        ]);

        $response = $this->actingAs($this->$user)
            ->getJson('/products?search=' . $product->name);
        $response
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonFragment(['id' => $product->getKey()]);
    }

    /**
     * @dataProvider authProvider
     */
    public function testSearchBySet($user): void
    {
        $this->$user->givePermissionTo('products.show');

        $set = ProductSet::factory()->create([
            'public' => true,
            'hide_on_index' => false,
        ]);

        $product = Product::factory()->create([
            'public' => true,
        ]);

        // Product not in set
        Product::factory()->create([
            'public' => true,
        ]);

        $set->products()->attach($product);

        $response = $this->actingAs($this->$user)
            ->getJson('/products?sets[]=' . $set->slug);

        $response
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonFragment(['id' => $product->getKey()]);
    }

    /**
     * @dataProvider authProvider
     */
    public function testSearchBySets($user): void
    {
        $this->$user->givePermissionTo('products.show');

        $set = ProductSet::factory()->create([
            'public' => true,
        ]);

        $set2 = ProductSet::factory()->create([
            'public' => true,
        ]);

        $product = Product::factory()->create([
            'public' => true,
        ]);

        $product2 = Product::factory()->create([
            'public' => true,
        ]);

        // Product not in set
        Product::factory()->create([
            'public' => true,
        ]);

        $set->products()->attach($product);
        $set2->products()->attach($product2);

        $response = $this->actingAs($this->$user)
            ->getJson('/products?sets[]=' . $set->slug . '&sets[]=' . $set2->slug);
        $response
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonFragment(['id' => $product->getKey()])
            ->assertJsonFragment(['id' => $product2->getKey()]);
    }

    /**
     * @dataProvider authProvider
     */
    public function testSearchBySetHiddenUnauthorized($user): void
    {
        $this->$user->givePermissionTo('products.show');

        $set = ProductSet::factory()->create([
            'public' => false,
        ]);

        $product = Product::factory()->create([
            'public' => true,
        ]);

        // Product not in set
        Product::factory()->create([
            'public' => true,
        ]);

        $set->products()->attach($product);

        $response = $this->actingAs($this->$user)
            ->getJson('/products?sets[]=' . $set->slug);

        $response->assertUnprocessable();
    }

    /**
     * @dataProvider authProvider
     */
    public function testSearchBySetHidden($user): void
    {
        $this->$user->givePermissionTo(['products.show', 'product_sets.show_hidden']);

        $set = ProductSet::factory()->create([
            'public' => true,
        ]);

        // Private set
        ProductSet::factory()->create([
            'public' => false,
        ]);

        $product = Product::factory()->create([
            'public' => true,
        ]);

        // Product not in set
        Product::factory()->create([
            'public' => true,
        ]);

        $set->products()->attach($product);

        $response = $this->actingAs($this->$user)
            ->getJson('/products?sets[]=' . $set->slug);

        $response
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonFragment(['id' => $product->getKey()]);
    }

    /**
     * @dataProvider authProvider
     */
    public function testSearchByParentSet($user): void
    {
        $this->$user->givePermissionTo('products.show');

        $this->getProductsByParentSet($this->$user, true, $product)
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonFragment(['id' => $product->getKey()]);
    }

    private function getProductsByParentSet(
        Authenticatable $user,
        bool $isChildSetPublic,
        ?Product &$productRef = null,
    ): TestResponse {
        $parentSet = ProductSet::factory()->create([
            'public' => true,
        ]);

        $childSet = ProductSet::factory()->create([
            'parent_id' => $parentSet->getKey(),
            'public' => $isChildSetPublic,
        ]);

        $productRef = Product::factory()->create([
            'public' => true,
        ]);

        // Product not in set
        Product::factory()->create([
            'public' => true,
        ]);

        $childSet->products()->attach($productRef);

        return $this->actingAs($user)
            ->getJson('/products?sets[]=' . $parentSet->slug);
    }

    /**
     * @dataProvider authProvider
     */
    public function testSearchByParentSetWithPrivateChildUnauthorized($user): void
    {
        $this->$user->givePermissionTo('products.show');

        $this->getProductsByParentSet($this->$user, false)
            ->assertOk()
            ->assertJsonCount(0, 'data');
    }

    /**
     * @dataProvider authProvider
     */
    public function testSearchByParentSetWithPrivateChild($user): void
    {
        $this->$user->givePermissionTo([
            'products.show',
            'product_sets.show_hidden',
        ]);

        $this->getProductsByParentSet($this->$user, false, $product)
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonFragment(['id' => $product->getKey()]);
    }
}
