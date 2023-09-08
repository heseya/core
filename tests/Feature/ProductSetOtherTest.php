<?php

namespace Tests\Feature;

use App\Listeners\WebHookEventListener;
use App\Models\Product;
use App\Models\WebHook;
use Domain\Product\Enums\ProductSalesChannelStatus;
use Domain\ProductSet\Events\ProductSetDeleted;
use Domain\ProductSet\ProductSet;
use Domain\SalesChannel\Models\SalesChannel;
use Domain\SalesChannel\SalesChannelRepository;
use Domain\Seo\Models\SeoMetadata;
use Illuminate\Events\CallQueuedListener;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Event;
use Support\Enum\Status;
use Tests\TestCase;

class ProductSetOtherTest extends TestCase
{
    public function testDeleteUnauthorized(): void
    {
        Event::fake([ProductSetDeleted::class]);

        $newSet = ProductSet::factory()->create([
            'public' => true,
        ]);

        $response = $this->deleteJson('/product-sets/id:' . $newSet->getKey());
        $response->assertForbidden();

        Event::assertNotDispatched(ProductSetDeleted::class);
    }

    /**
     * @dataProvider authProvider
     */
    public function testDelete(string $user): void
    {
        $this->{$user}->givePermissionTo('product_sets.remove');

        Event::fake([ProductSetDeleted::class]);

        $newSet = ProductSet::factory()->create([
            'public' => true,
        ]);

        $response = $this->actingAs($this->{$user})->deleteJson(
            '/product-sets/id:' . $newSet->getKey(),
        );
        $response->assertNoContent();
        $this->assertSoftDeleted($newSet);

        Event::assertDispatched(ProductSetDeleted::class);
    }

    /**
     * @dataProvider authProvider
     */
    public function testDeleteWithWebHook(string $user): void
    {
        $this->{$user}->givePermissionTo('product_sets.remove');

        $webHook = WebHook::factory()->create([
            'events' => [
                'ProductSetDeleted',
            ],
            'model_type' => $this->{$user}::class,
            'creator_id' => $this->{$user}->getKey(),
            'with_issuer' => true,
            'with_hidden' => false,
        ]);

        Bus::fake();

        $newSet = ProductSet::factory()->create([
            'public' => true,
        ]);

        $response = $this->actingAs($this->{$user})->deleteJson(
            '/product-sets/id:' . $newSet->getKey(),
        );
        $response->assertNoContent();
        $this->assertSoftDeleted($newSet);

        Bus::assertDispatched(CallQueuedListener::class, function ($job) {
            return $job->class === WebHookEventListener::class
                && $job->data[0] instanceof ProductSetDeleted;
        });
    }

    /**
     * @dataProvider authProvider
     */
    public function testDeleteWithProducts(string $user): void
    {
        $this->{$user}->givePermissionTo('product_sets.remove');

        Event::fake([ProductSetDeleted::class]);

        $newSet = ProductSet::factory()->create([
            'public' => true,
        ]);

        $product1 = Product::factory()->create();
        $product2 = Product::factory()->create();
        $product3 = Product::factory()->create();

        $newSet->products()->sync([
            $product1->getKey(),
            $product2->getKey(),
            $product3->getKey(),
        ]);

        $response = $this->actingAs($this->{$user})->delete(
            '/product-sets/id:' . $newSet->getKey(),
        );
        $response->assertNoContent();
        $this->assertSoftDeleted($newSet);

        Event::assertDispatched(ProductSetDeleted::class);
    }

    /**
     * @dataProvider authProvider
     */
    public function testDeleteWithSubsets(string $user): void
    {
        $this->{$user}->givePermissionTo('product_sets.remove');

        Event::fake([ProductSetDeleted::class]);

        $newSet = ProductSet::factory()->create([
            'public' => true,
        ]);

        $subset1 = ProductSet::factory()->create([
            'public' => true,
            'parent_id' => $newSet->getKey(),
        ]);

        $subset2 = ProductSet::factory()->create([
            'public' => true,
            'parent_id' => $newSet->getKey(),
        ]);

        $subset3 = ProductSet::factory()->create([
            'public' => true,
            'parent_id' => $newSet->getKey(),
        ]);

        $response = $this->actingAs($this->{$user})->delete(
            '/product-sets/id:' . $newSet->getKey(),
        );
        $response->assertNoContent();
        $this->assertSoftDeleted($newSet);
        $this->assertSoftDeleted($subset1);
        $this->assertSoftDeleted($subset2);
        $this->assertSoftDeleted($subset3);

        Event::assertDispatched(ProductSetDeleted::class);
    }

    /**
     * @dataProvider authProvider
     */
    public function testDeleteWithSeo(string $user): void
    {
        $this->{$user}->givePermissionTo('product_sets.remove');

        $newSet = ProductSet::factory()->create([
            'public' => true,
        ]);
        $seo = SeoMetadata::factory()->create();
        $newSet->seo()->save($seo);

        $response = $this->actingAs($this->{$user})->deleteJson(
            '/product-sets/id:' . $newSet->getKey(),
        );
        $response->assertNoContent();
        $this->assertSoftDeleted($newSet);
        $this->assertSoftDeleted($seo);
    }

    /**
     * @dataProvider authProvider
     */
    public function testReorderRoot(string $user): void
    {
        $this->{$user}->givePermissionTo('product_sets.edit');

        $set1 = ProductSet::factory()->create();
        $set2 = ProductSet::factory()->create();
        $set3 = ProductSet::factory()->create();

        $response = $this->actingAs($this->{$user})->postJson('/product-sets/reorder', [
            'product_sets' => [
                $set3->getKey(),
                $set2->getKey(),
                $set1->getKey(),
            ],
        ]);
        $response->assertNoContent();

        $this->assertDatabaseHas('product_sets', [
            'id' => $set3->getKey(),
            'order' => 0,
        ]);

        $this->assertDatabaseHas('product_sets', [
            'id' => $set2->getKey(),
            'order' => 1,
        ]);

        $this->assertDatabaseHas('product_sets', [
            'id' => $set1->getKey(),
            'order' => 2,
        ]);
    }

    /**
     * @dataProvider authProvider
     */
    public function testReorderChildren(string $user): void
    {
        $this->{$user}->givePermissionTo('product_sets.edit');

        $parent = ProductSet::factory()->create();
        $set1 = ProductSet::factory()->create([
            'parent_id' => $parent->getKey(),
        ]);
        $set2 = ProductSet::factory()->create([
            'parent_id' => $parent->getKey(),
        ]);
        $set3 = ProductSet::factory()->create([
            'parent_id' => $parent->getKey(),
        ]);

        $response = $this->actingAs($this->{$user})->postJson('/product-sets/reorder/id:' . $parent->getKey(), [
            'product_sets' => [
                $set3->getKey(),
                $set2->getKey(),
                $set1->getKey(),
            ],
        ]);
        $response->assertNoContent();

        $this->assertDatabaseHas('product_sets', [
            'id' => $set3->getKey(),
            'order' => 0,
        ]);

        $this->assertDatabaseHas('product_sets', [
            'id' => $set2->getKey(),
            'order' => 1,
        ]);

        $this->assertDatabaseHas('product_sets', [
            'id' => $set1->getKey(),
            'order' => 2,
        ]);
    }

    /**
     * @dataProvider authProvider
     */
    public function testAttachProducts(string $user): void
    {
        $this->{$user}->givePermissionTo('product_sets.edit');

        $set = ProductSet::factory()->create();

        $product1 = Product::factory()->create();
        $product2 = Product::factory()->create();
        $product3 = Product::factory()->create();

        $response = $this->actingAs($this->{$user})->postJson(
            '/product-sets/id:' . $set->getKey() . '/products',
            [
                'products' => [
                    $product1->getKey(),
                    $product2->getKey(),
                    $product3->getKey(),
                ],
            ],
        );

        $response
            ->assertOk()
            ->assertJsonCount(3, 'data');

        $this->assertDatabaseHas('product_set_product', [
            'product_id' => $product1->getKey(),
            'product_set_id' => $set->getKey(),
        ]);

        $this->assertDatabaseHas('product_set_product', [
            'product_id' => $product2->getKey(),
            'product_set_id' => $set->getKey(),
        ]);

        $this->assertDatabaseHas('product_set_product', [
            'product_id' => $product3->getKey(),
            'product_set_id' => $set->getKey(),
        ]);
    }

    /**
     * @dataProvider authProvider
     */
    public function testDetachProducts(string $user): void
    {
        $this->{$user}->givePermissionTo('product_sets.edit');

        $set = ProductSet::factory()->create();

        $product1 = Product::factory()->create();
        $product2 = Product::factory()->create();
        $product3 = Product::factory()->create();

        $set->products()->sync([
            $product1->getKey(),
            $product2->getKey(),
            $product3->getKey(),
        ]);

        $response = $this->actingAs($this->{$user})->postJson(
            '/product-sets/id:' . $set->getKey() . '/products',
            [
                'products' => [],
            ],
        );

        $response
            ->assertOk()
            ->assertJsonCount(0, 'data');

        $this->assertDatabaseMissing('product_set_product', [
            'product_id' => $product1->getKey(),
            'product_set_id' => $set->getKey(),
        ]);

        $this->assertDatabaseMissing('product_set_product', [
            'product_id' => $product2->getKey(),
            'product_set_id' => $set->getKey(),
        ]);

        $this->assertDatabaseMissing('product_set_product', [
            'product_id' => $product3->getKey(),
            'product_set_id' => $set->getKey(),
        ]);
    }

    /**
     * @dataProvider authProvider
     */
    public function testShowProductsUnauthorized(string $user): void
    {
        $set = ProductSet::factory()->create([
            'public' => true,
        ]);

        $response = $this->actingAs($this->{$user})->getJson(
            '/product-sets/id:' . $set->getKey() . '/products',
        );

        $response->assertForbidden();
    }

    /**
     * @dataProvider authProvider
     */
    public function testShowProducts(string $user): void
    {
        $this->{$user}->givePermissionTo('product_sets.show_details');

        $salesChannel = app(SalesChannelRepository::class)->getDefault();

        $set = ProductSet::factory()->create([
            'public' => true,
        ]);

        $product1 = Product::factory()->create();
        $product1->salesChannels()->syncWithPivotValues($salesChannel, ['availability_status' => ProductSalesChannelStatus::PUBLIC->value]);

        $product2 = Product::factory()->create();
        $product2->salesChannels()->syncWithPivotValues($salesChannel, ['availability_status' => ProductSalesChannelStatus::HIDDEN->value]);

        $product3 = Product::factory()->create();
        $product3->salesChannels()->syncWithPivotValues($salesChannel, ['availability_status' => ProductSalesChannelStatus::PUBLIC->value]);

        $set->products()->sync([
            $product1->getKey(),
            $product2->getKey(),
        ]);

        $response = $this->actingAs($this->{$user})
            ->getJson('/product-sets/id:' . $set->getKey() . '/products');

        $response
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJson([
                'data' => [
                    [
                        'id' => $product1->getKey(),
                        'name' => $product1->name,
                        'slug' => $product1->slug,
                    ],
                ],
            ]);
    }

    /**
     * @dataProvider authProvider
     */
    public function testShowProductsWrongId(string $user): void
    {
        $this->{$user}->givePermissionTo('product_sets.show_details');

        $salesChannel = app(SalesChannelRepository::class)->getDefault();

        $set = ProductSet::factory()->create([
            'public' => true,
        ]);

        $product1 = Product::factory()->create();
        $product1->salesChannels()->syncWithPivotValues($salesChannel, ['availability_status' => ProductSalesChannelStatus::PUBLIC->value]);

        $product2 = Product::factory()->create();
        $product2->salesChannels()->syncWithPivotValues($salesChannel, ['availability_status' => ProductSalesChannelStatus::HIDDEN->value]);

        $product3 = Product::factory()->create();
        $product3->salesChannels()->syncWithPivotValues($salesChannel, ['availability_status' => ProductSalesChannelStatus::PUBLIC->value]);

        $set->products()->sync([
            $product1->getKey(),
            $product2->getKey(),
        ]);

        $this->actingAs($this->{$user})
            ->getJson('/product-sets/id:its-not-uuid/products')
            ->assertNotFound();

        $this->actingAs($this->{$user})
            ->getJson('/product-sets/id:' . $set->getKey() . $set->getKey() . '/products')
            ->assertNotFound();
    }

    /**
     * @dataProvider authProvider
     */
    public function testShowProductsHidden(string $user): void
    {
        $this->{$user}->givePermissionTo(['product_sets.show_details', 'product_sets.show_hidden']);

        $salesChannel = app(SalesChannelRepository::class)->getDefault() ?? SalesChannel::query()->firstOrCreate(['status' => Status::ACTIVE->value], SalesChannel::factory()->definition());

        $set = ProductSet::factory()->create([
            'public' => true,
        ]);

        $product1 = Product::factory()->create();
        $product1->salesChannels()->syncWithPivotValues($salesChannel, ['availability_status' => ProductSalesChannelStatus::PUBLIC->value]);

        $product2 = Product::factory()->create();
        $product2->salesChannels()->syncWithPivotValues($salesChannel, ['availability_status' => ProductSalesChannelStatus::HIDDEN->value]);

        $set->products()->sync([
            $product1->getKey(),
            $product2->getKey(),
        ]);

        $response = $this->actingAs($this->{$user})->getJson(
            '/product-sets/id:' . $set->getKey() . '/products',
        );

        $response
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonFragment([
                'id' => $product1->getKey(),
                'name' => $product1->name,
                'slug' => $product1->slug,
            ])
            ->assertJsonFragment([
                'id' => $product2->getKey(),
                'name' => $product2->name,
                'slug' => $product2->slug,
            ]);
    }

    /**
     * @dataProvider authProvider
     */
    public function testProductReorderInSetHigherOrder(string $user): void
    {
        $this->{$user}->givePermissionTo(['product_sets.edit']);

        $set = $this->prepareOrderData();

        /** @var Product $product */
        $product = $set->products->firstWhere('name', 'five');

        $this->actingAs($this->{$user})->json(
            'POST',
            '/product-sets/id:' . $set->getKey() . '/products/reorder',
            [
                'products' => [
                    [
                        'id' => $product->getKey(),
                        'order' => 0,
                    ],
                ],
            ]
        );

        $this
            ->assertDatabaseHas('product_set_product', [
                'product_id' => $product->getKey(),
                'order' => 0,
            ])
            ->assertDatabaseHas('product_set_product', [
                'order' => 1,
            ])
            ->assertDatabaseHas('product_set_product', [
                'order' => 2,
            ])
            ->assertDatabaseHas('product_set_product', [
                'order' => 3,
            ])
            ->assertDatabaseHas('product_set_product', [
                'order' => 4,
            ]);
    }

    /**
     * @dataProvider authProvider
     */
    public function testProductReorderInSetLowerOrder(string $user): void
    {
        $this->{$user}->givePermissionTo(['product_sets.edit']);

        $set = $this->prepareOrderData();

        /** @var Product $product */
        $product = $set->products->firstWhere('name', 'one');

        $this->actingAs($this->{$user})->json(
            'POST',
            '/product-sets/id:' . $set->getKey() . '/products/reorder',
            [
                'products' => [
                    [
                        'id' => $product->getKey(),
                        'order' => 4,
                    ],
                ],
            ]
        );

        $this
            ->assertDatabaseHas('product_set_product', [
                'product_id' => $product->getKey(),
                'order' => 4,
            ])
            ->assertDatabaseHas('product_set_product', [
                'order' => 0,
            ])
            ->assertDatabaseHas('product_set_product', [
                'order' => 1,
            ])
            ->assertDatabaseHas('product_set_product', [
                'order' => 2,
            ])
            ->assertDatabaseHas('product_set_product', [
                'order' => 3,
            ]);
    }

    /**
     * @dataProvider authProvider
     */
    public function testProductReorderInSetSameOrder(string $user): void
    {
        $this->{$user}->givePermissionTo(['product_sets.edit']);

        $set = $this->prepareOrderData();

        /** @var Product $product */
        $product = $set->products->firstWhere('name', 'three');

        $this->actingAs($this->{$user})->json(
            'POST',
            '/product-sets/id:' . $set->getKey() . '/products/reorder',
            [
                'products' => [
                    [
                        'id' => $product->getKey(),
                        'order' => 2,
                    ],
                ],
            ]
        );

        $this
            ->assertDatabaseHas('product_set_product', [
                'product_id' => $product->getKey(),
                'order' => 2,
            ])
            ->assertDatabaseHas('product_set_product', [
                'order' => 0,
            ])
            ->assertDatabaseHas('product_set_product', [
                'order' => 1,
            ])
            ->assertDatabaseHas('product_set_product', [
                'order' => 3,
            ])
            ->assertDatabaseHas('product_set_product', [
                'order' => 4,
            ]);
    }

    /**
     * @dataProvider authProvider
     */
    public function testProductReorderInSetOrderOutOfSize(string $user): void
    {
        $this->{$user}->givePermissionTo(['product_sets.edit']);

        $set = $this->prepareOrderData();

        /** @var Product $product */
        $product = $set->products->where('name', 'three')->first();

        $this->actingAs($this->{$user})->json(
            'POST',
            '/product-sets/id:' . $set->getKey() . '/products/reorder',
            [
                'products' => [
                    [
                        'id' => $product->getKey(),
                        'order' => 9999,
                    ],
                ],
            ]
        );

        $this
            ->assertDatabaseHas('product_set_product', [
                'product_id' => $product->getKey(),
                'order' => 4,
            ])
            ->assertDatabaseHas('product_set_product', [
                'order' => 0,
            ])
            ->assertDatabaseHas('product_set_product', [
                'order' => 1,
            ])
            ->assertDatabaseHas('product_set_product', [
                'order' => 2,
            ])
            ->assertDatabaseHas('product_set_product', [
                'order' => 3,
            ]);
    }

    /**
     * @dataProvider authProvider
     */
    public function testProductReorderWhenWasNull(string $user): void
    {
        $this->{$user}->givePermissionTo(['product_sets.edit']);

        $set = ProductSet::factory()->create([
            'public' => true,
        ]);

        $product1 = Product::factory()->create([
            'name' => 'one',
        ]);
        $product2 = Product::factory()->create([
            'name' => 'two',
        ]);

        $set->products()->attach($product1->getKey(), ['order' => null]);
        $set->products()->attach($product2->getKey(), ['order' => null]);
        $this->actingAs($this->{$user})->json(
            'POST',
            '/product-sets/id:' . $set->getKey() . '/products/reorder',
            [
                'products' => [
                    [
                        'id' => $product1->getKey(),
                        'order' => 0,
                    ],
                ],
            ]
        );

        $this
            ->assertDatabaseHas('product_set_product', [
                'product_id' => $product1->getKey(),
                'order' => 0,
            ]);
    }

    /**
     * @dataProvider authProvider
     */
    public function testProductReorderHandleNulls(string $user): void
    {
        $this->{$user}->givePermissionTo(['product_sets.edit']);

        $set = ProductSet::factory()->create([
            'public' => true,
        ]);

        $product1 = Product::factory()->create([
            'name' => 'one',
        ]);
        $product2 = Product::factory()->create([
            'name' => 'two',
        ]);
        $product3 = Product::factory()->create([
            'name' => 'three',
        ]);
        $product4 = Product::factory()->create([
            'name' => 'four',
        ]);

        $set->products()->attach($product1->getKey(), ['order' => 0]);
        $set->products()->attach($product2->getKey(), ['order' => 1]);
        $set->products()->attach($product3->getKey(), ['order' => null]);
        $set->products()->attach($product4->getKey(), ['order' => null]);
        $this->actingAs($this->{$user})->json(
            'POST',
            '/product-sets/id:' . $set->getKey() . '/products/reorder',
            [
                'products' => [
                    [
                        'id' => $product1->getKey(),
                        'order' => 0,
                    ],
                ],
            ]
        );

        $this
            ->assertDatabaseHas('product_set_product', [
                'product_id' => $product1->getKey(),
                'order' => 0,
            ])
            ->assertDatabaseHas('product_set_product', [
                'order' => 1,
            ])
            ->assertDatabaseHas('product_set_product', [
                'order' => 2,
            ])
            ->assertDatabaseHas('product_set_product', [
                'order' => 3,
            ]);
    }

    private function prepareOrderData(): ProductSet
    {
        $set = ProductSet::factory()->create([
            'public' => true,
        ]);

        $product1 = Product::factory()->create([
            'public' => true,
            'name' => 'one',
        ]);
        $product2 = Product::factory()->create([
            'public' => false,
            'name' => 'two',
        ]);
        $product3 = Product::factory()->create([
            'public' => false,
            'name' => 'three',
        ]);
        $product4 = Product::factory()->create([
            'public' => false,
            'name' => 'four',
        ]);
        $product5 = Product::factory()->create([
            'public' => false,
            'name' => 'five',
        ]);

        $set->products()->attach($product1->getKey(), ['order' => 0]);
        $set->products()->attach($product2->getKey(), ['order' => 1]);
        $set->products()->attach($product3->getKey(), ['order' => 2]);
        $set->products()->attach($product4->getKey(), ['order' => 3]);
        $set->products()->attach($product5->getKey(), ['order' => 4]);

        return $set;
    }
}
