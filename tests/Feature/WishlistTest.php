<?php

namespace Tests\Feature;

use App\Enums\ExceptionsEnums\Exceptions;
use App\Enums\ValidationError;
use App\Models\Product;
use App\Models\WishlistProduct;
use Tests\TestCase;

class WishlistTest extends TestCase
{
    protected Product $product;

    protected array $items;
    protected array $address;
    protected array $expected_structure;

    public function setUp(): void
    {
        parent::setUp();
        $this->product = Product::factory()->create([
            'public' => true,
            'name' => 'test product',
        ]);

        $this->expected_structure = [
            'id',
            'product' => [
                'id',
                'slug',
                'name',
                'price',
                'price_min',
                'price_max',
                'price_min_initial',
                'price_max_initial',
                'public',
                'visible',
                'available',
                'quantity_step',
                'google_product_category',
                'vat_rate',
                'shipping_time',
                'shipping_date',
                'cover',
                'tags',
                'has_schemas',
                'quantity',
                'metadata',
                'attributes',
            ],
            'created_at',
        ];
    }

    /**
     * @dataProvider authProvider
     */
    public function testIndex($user): void
    {
        $this->$user->givePermissionTo('profile.wishlist_manage');

        WishlistProduct::create([
            'product_id' => $this->product->getKey(),
            'user_id' => $this->$user->getKey(),
            'user_type' => $this->$user::class,
        ]);

        $this->actingAs($this->$user)->json('GET', '/wishlist')
            ->assertOk()
            ->assertJsonStructure([
                'data' => [
                    0 => $this->expected_structure,
                ],
            ])
            ->assertJson([
                'data' => [
                    0 => [
                        'product' => [
                            'id' => $this->product->getKey(),
                            'name' => $this->product->name,
                            'slug' => $this->product->slug,
                            'public' => $this->product->public,
                        ],
                    ],
                ],
            ]);
    }

    public function testIndexUnauthorized(): void
    {
        WishlistProduct::create([
            'product_id' => $this->product->getKey(),
            'user_id' => $this->user->getKey(),
            'user_type' => $this->user::class,
        ]);

        $this->json('GET', '/wishlist')
            ->assertForbidden();
    }

    /**
     * @dataProvider authProvider
     */
    public function testShow($user): void
    {
        $this->$user->givePermissionTo('profile.wishlist_manage');

        WishlistProduct::create([
            'product_id' => Product::factory()->create()->getKey(),
            'user_id' => $this->$user->getKey(),
            'user_type' => $this->$user::class,
        ]);

        WishlistProduct::create([
            'product_id' => $this->product->getKey(),
            'user_id' => $this->$user->getKey(),
            'user_type' => $this->$user::class,
        ]);

        $this->actingAs($this->$user)->json('GET', '/wishlist/id:' . $this->product->getKey())
            ->assertOk()
            ->assertJsonStructure(['data' => $this->expected_structure])
            ->assertJson([
                'data' => [
                    'product' => [
                        'id' => $this->product->getKey(),
                        'name' => $this->product->name,
                        'slug' => $this->product->slug,
                        'public' => $this->product->public,
                    ],
                ],
            ]);
    }

    /**
     * @dataProvider authProvider
     */
    public function testShowMissing($user): void
    {
        $this->$user->givePermissionTo('profile.wishlist_manage');

        WishlistProduct::create([
            'product_id' => Product::factory()->create()->getKey(),
            'user_id' => $this->$user->getKey(),
        ]);

        $newProduct = Product::factory()->create(['public' => true]);

        $this->actingAs($this->$user)->json('GET', '/wishlist/id:' . $newProduct->getKey())
            ->assertNotFound();
    }

    public function testShowUnauthorized(): void
    {
        WishlistProduct::create([
            'product_id' => Product::factory()->create()->getKey(),
            'user_id' => $this->user->getKey(),
            'user_type' => $this->user::class,
        ]);

        WishlistProduct::create([
            'product_id' => $this->product->getKey(),
            'user_id' => $this->user->getKey(),
            'user_type' => $this->user::class,
        ]);

        $this->json('GET', '/wishlist/id:' . $this->product->getKey())
            ->assertForbidden();
    }

    /**
     * @dataProvider authProvider
     */
    public function testStore($user): void
    {
        $this->$user->givePermissionTo('profile.wishlist_manage');

        $this->actingAs($this->$user)->json('POST', '/wishlist', [
            'product_id' => $this->product->getKey(),
        ])
            ->assertCreated()
            ->assertJsonStructure(['data' => $this->expected_structure])
            ->assertJson([
                'data' => [
                    'product' => [
                        'id' => $this->product->getKey(),
                        'name' => $this->product->name,
                        'slug' => $this->product->slug,
                        'public' => $this->product->public,
                    ],
                ],
            ]);

        $this->assertDatabaseHas('wishlist_products', [
            'user_id' => $this->$user->getKey(),
            'product_id' => $this->product->getKey(),
        ]);
    }

    public function testStoreUnauthorized(): void
    {
        $this->actingAs($this->user)->json('POST', '/wishlist', [
            'product_id' => $this->product->getKey(),
        ])
            ->assertForbidden();
    }

    /**
     * @dataProvider authProvider
     */
    public function testStoreAlreadyStored($user): void
    {
        $this->$user->givePermissionTo('profile.wishlist_manage');

        WishlistProduct::create([
            'product_id' => $this->product->getKey(),
            'user_id' => $this->$user->getKey(),
        ]);

        $this->actingAs($this->$user)->json('POST', '/wishlist', [
            'product_id' => $this->product->getKey(),
        ])
            ->assertStatus(422)
            ->assertJsonFragment([
                'key' => ValidationError::UNIQUE,
            ]);
    }

    /**
     * @dataProvider authProvider
     */
    public function testStoreAfterSoftDelete($user): void
    {
        $this->$user->givePermissionTo('profile.wishlist_manage');

        $wishlistProduct = WishlistProduct::create([
            'product_id' => $this->product->getKey(),
            'user_id' => $this->user->getKey(),
            'user_type' => $this->user::class,
        ]);

        $wishlistProduct->delete();

        $this->actingAs($this->$user)->json('POST', '/wishlist', [
            'product_id' => $this->product->getKey(),
        ])
            ->assertCreated()
            ->assertJsonStructure(['data' => $this->expected_structure])
            ->assertJson([
                'data' => [
                    'product' => [
                        'id' => $this->product->getKey(),
                        'name' => $this->product->name,
                        'slug' => $this->product->slug,
                        'public' => $this->product->public,
                    ],
                ],
            ]);

        $this->assertDatabaseHas('wishlist_products', [
            'user_id' => $this->$user->getKey(),
            'product_id' => $this->product->getKey(),
        ]);
    }

    /**
     * @dataProvider authProvider
     */
    public function testDelete($user): void
    {
        $this->$user->givePermissionTo('profile.wishlist_manage');

        WishlistProduct::create([
            'product_id' => $this->product->getKey(),
            'user_id' => $this->$user->getKey(),
            'user_type' => $this->$user::class,
        ]);

        $this->actingAs($this->$user)->json('DELETE', '/wishlist/id:' . $this->product->getKey())
            ->assertNoContent();

        $this->assertSoftDeleted('wishlist_products', [
            'product_id' => $this->product->getKey(),
            'user_id' => $this->$user->getKey(),
        ]);
    }

    public function testDeleteUnauthorized(): void
    {
        WishlistProduct::create([
            'product_id' => $this->product->getKey(),
            'user_id' => $this->user->getKey(),
            'user_type' => $this->user::class,
        ]);

        $this->json('DELETE', '/wishlist/id:' . $this->product->getKey())
            ->assertForbidden();
    }

    /**
     * @dataProvider authProvider
     */
    public function testDeleteDoesntExist($user): void
    {
        $this->$user->givePermissionTo('profile.wishlist_manage');

        $this->actingAs($this->$user)->json('DELETE', '/wishlist/id:' . $this->product->getKey())
            ->assertStatus(422)
            ->assertJsonFragment([
                'key' => Exceptions::getKey(Exceptions::PRODUCT_IS_NOT_ON_WISHLIST),
            ]);
    }

    public function testDeleteAllUnauthorized(): void
    {
        $this->actingAs($this->user)->json('DELETE', '/wishlist')->assertForbidden();
    }

    /**
     * @dataProvider authProvider
     */
    public function testDeleteAll($user): void
    {
        $this->$user->givePermissionTo('profile.wishlist_manage');

        $wishlistProduct1 = $this->$user->wishlistProducts()->create([
            'product_id' => $this->product->getKey(),
        ]);

        $product = Product::factory()->create([
            'public' => true,
            'name' => 'another test product',
        ]);

        $wishlistProduct2 = $this->$user->wishlistProducts()->create([
            'product_id' => $product->getKey(),
        ]);

        $this->actingAs($this->$user)->json('DELETE', '/wishlist')->assertNoContent();

        $this->assertSoftDeleted($wishlistProduct1);
        $this->assertSoftDeleted($wishlistProduct2);
    }
}
