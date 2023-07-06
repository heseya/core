<?php

namespace Tests\Feature;

use App\Models\ProductSet;
use Tests\TestCase;

class FavouriteTest extends TestCase
{
    private ProductSet $productSet;

    public function setUp(): void
    {
        parent::setUp();
        $this->productSet = ProductSet::factory()->create([
            'public' => true,
            'name' => 'test product set',
        ]);
    }

    public function testIndexUnauthorized(): void
    {
        $this->user->favouriteProductSets()->create([
            'product_set_id' => $this->productSet->getKey(),
        ]);

        $this->actingAs($this->user)->json('GET', '/product-sets/favourites')
            ->assertForbidden();
    }

    /**
     * @dataProvider authProvider
     */
    public function testIndex($user): void
    {
        $this->{$user}->givePermissionTo('profile.favourites_manage');

        $this->{$user}->favouriteProductSets()->create([
            'product_set_id' => $this->productSet->getKey(),
        ]);

        $this->actingAs($this->{$user})->json('GET', '/product-sets/favourites')
            ->assertOk()
            ->assertJson([
                'data' => [
                    0 => [
                        'product_set' => [
                            'id' => $this->productSet->getKey(),
                            'name' => $this->productSet->name,
                            'public' => $this->productSet->public,
                        ],
                    ],
                ],
            ]);
    }

    public function testShowUnauthorized(): void
    {
        $this->user->favouriteProductSets()->create([
            'product_set_id' => $this->productSet->getKey(),
        ]);

        $this
            ->actingAs($this->user)
            ->json('GET', '/product-sets/favourites/id:' . $this->productSet->getKey())
            ->assertForbidden();
    }

    /**
     * @dataProvider authProvider
     */
    public function testShow($user): void
    {
        $this->{$user}->givePermissionTo('profile.favourites_manage');

        $this->{$user}->favouriteProductSets()->create([
            'product_set_id' => $this->productSet->getKey(),
        ]);

        $this
            ->actingAs($this->{$user})
            ->json('GET', '/product-sets/favourites/id:' . $this->productSet->getKey())
            ->assertOk()
            ->assertJson([
                'data' => [
                    'product_set' => [
                        'id' => $this->productSet->getKey(),
                        'name' => $this->productSet->name,
                        'public' => $this->productSet->public,
                    ],
                ],
            ]);
    }

    public function testStoreUnauthorized(): void
    {
        $this
            ->actingAs($this->user)
            ->json('POST', '/product-sets/favourites', [
                'product_set_id' => $this->productSet->getKey(),
            ])
            ->assertForbidden();
    }

    /**
     * @dataProvider authProvider
     */
    public function testStore($user): void
    {
        $this->{$user}->givePermissionTo('profile.favourites_manage');

        $this
            ->actingAs($this->{$user})
            ->json('POST', '/product-sets/favourites', [
                'product_set_id' => $this->productSet->getKey(),
            ])
            ->assertJson([
                'data' => [
                    'product_set' => [
                        'id' => $this->productSet->getKey(),
                        'name' => $this->productSet->name,
                        'public' => $this->productSet->public,
                    ],
                ],
            ]);
    }

    /**
     * @dataProvider authProvider
     */
    public function testStoreAlreadyLiked($user): void
    {
        $this->{$user}->givePermissionTo('profile.favourites_manage');

        $this->{$user}->favouriteProductSets()->create([
            'product_set_id' => $this->productSet->getKey(),
        ]);

        $this
            ->actingAs($this->{$user})
            ->json('POST', '/product-sets/favourites', [
                'product_set_id' => $this->productSet->getKey(),
            ])
            ->assertUnprocessable();
    }

    /**
     * @dataProvider authProvider
     */
    public function testStoreAnother($user): void
    {
        $this->{$user}->givePermissionTo('profile.favourites_manage');

        $this->{$user}->favouriteProductSets()->create([
            'product_set_id' => $this->productSet->getKey(),
        ]);

        $productSet = ProductSet::factory()->create([
            'public' => true,
            'name' => 'another product set',
        ]);

        $this
            ->actingAs($this->{$user})
            ->json('POST', '/product-sets/favourites', [
                'product_set_id' => $productSet->getKey(),
            ])
            ->assertJson([
                'data' => [
                    'product_set' => [
                        'id' => $productSet->getKey(),
                        'name' => $productSet->name,
                        'public' => $productSet->public,
                    ],
                ],
            ]);
    }

    /**
     * @dataProvider authProvider
     */
    public function testStoreAfterDelete($user): void
    {
        $this->{$user}->givePermissionTo('profile.favourites_manage');

        $favouriteProductSet = $this->{$user}->favouriteProductSets()->create([
            'product_set_id' => $this->productSet->getKey(),
        ]);

        $favouriteProductSet->delete();

        $this
            ->actingAs($this->{$user})
            ->json('POST', '/product-sets/favourites', [
                'product_set_id' => $this->productSet->getKey(),
            ])
            ->assertJson([
                'data' => [
                    'product_set' => [
                        'id' => $this->productSet->getKey(),
                        'name' => $this->productSet->name,
                        'public' => $this->productSet->public,
                    ],
                ],
            ]);

        $this->assertSoftDeleted($favouriteProductSet);

        $this->assertDatabaseHas('favourite_product_sets', [
            'user_id' => $this->{$user}->getKey(),
            'product_set_id' => $this->productSet->getKey(),
        ]);
    }

    public function testDeleteUnauthorized(): void
    {
        $this->user->favouriteProductSets()->create([
            'product_set_id' => $this->productSet->getKey(),
        ]);

        $this
            ->actingAs($this->user)
            ->json('DELETE', '/product-sets/favourites/id:' . $this->productSet->getKey())
            ->assertForbidden();
    }

    /**
     * @dataProvider authProvider
     */
    public function testDelete($user): void
    {
        $this->{$user}->givePermissionTo('profile.favourites_manage');

        $favouriteProductSet = $this->{$user}->favouriteProductSets()->create([
            'product_set_id' => $this->productSet->getKey(),
        ]);

        $this
            ->actingAs($this->{$user})
            ->json('DELETE', '/product-sets/favourites/id:' . $this->productSet->getKey())
            ->assertNoContent();

        $this->assertSoftDeleted($favouriteProductSet);
    }

    /**
     * @dataProvider authProvider
     */
    public function testDeleteDoesntExist($user): void
    {
        $this->{$user}->givePermissionTo('profile.favourites_manage');

        $this
            ->actingAs($this->{$user})
            ->json('DELETE', '/product-sets/favourites/id:' . $this->productSet->getKey())
            ->assertUnprocessable();
    }

    /**
     * @dataProvider authProvider
     */
    public function testDeleteAll($user): void
    {
        $this->{$user}->givePermissionTo('profile.favourites_manage');

        $favouriteProductSet1 = $this->{$user}->favouriteProductSets()->create([
            'product_set_id' => $this->productSet->getKey(),
        ]);

        $productSet = ProductSet::factory()->create([
            'public' => true,
            'name' => 'another product set',
        ]);

        $favouriteProductSet2 = $this->{$user}->favouriteProductSets()->create([
            'product_set_id' => $productSet->getKey(),
        ]);

        $this
            ->actingAs($this->{$user})
            ->json('DELETE', '/product-sets/favourites')
            ->assertNoContent();

        $this->assertSoftDeleted($favouriteProductSet1);
        $this->assertSoftDeleted($favouriteProductSet2);
    }
}
