<?php

namespace Tests\Feature;

use App\Events\ProductSetDeleted;
use App\Http\Resources\ProductSetResource;
use App\Listeners\WebHookEventListener;
use App\Models\Product;
use App\Models\ProductSet;
use App\Models\WebHook;
use Illuminate\Events\CallQueuedListener;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Event;
use Spatie\WebhookServer\CallWebhookJob;
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
    public function testDelete($user): void
    {
        $this->$user->givePermissionTo('product_sets.remove');

        Event::fake([ProductSetDeleted::class]);

        $newSet = ProductSet::factory()->create([
            'public' => true,
        ]);

        $response = $this->actingAs($this->user)->deleteJson(
            '/product-sets/id:' . $newSet->getKey(),
        );
        $response->assertNoContent();
        $this->assertSoftDeleted($newSet);

        Event::assertDispatched(ProductSetDeleted::class);
    }

    /**
     * @dataProvider authProvider
     */
    public function testDeleteWithWebHook($user): void
    {
        $this->$user->givePermissionTo('product_sets.remove');

        $webHook = WebHook::factory()->create([
            'events' => [
                'ProductSetDeleted'
            ],
            'model_type' => $this->$user::class,
            'creator_id' => $this->$user->getKey(),
            'with_issuer' => true,
            'with_hidden' => false,
        ]);

        Bus::fake();

        $newSet = ProductSet::factory()->create([
            'public' => true,
        ]);

        $response = $this->actingAs($this->$user)->deleteJson(
            '/product-sets/id:' . $newSet->getKey(),
        );
        $response->assertNoContent();
        $this->assertSoftDeleted($newSet);

        Bus::assertDispatched(CallQueuedListener::class, function ($job) {
            return $job->class === WebHookEventListener::class
                && $job->data[0] instanceof ProductSetDeleted;
        });

        $event = new ProductSetDeleted($newSet);
        $listener = new WebHookEventListener();
        $listener->handle($event);

        Bus::assertDispatched(CallWebhookJob::class, function ($job) use ($webHook, $newSet) {
            $payload = $job->payload;
            return $job->webhookUrl === $webHook->url
                && isset($job->headers['Signature'])
                && $payload['data']['id'] === $newSet->getKey()
                && $payload['data_type'] === 'ProductSet'
                && $payload['event'] === 'ProductSetDeleted';
        });
    }

    /**
     * @dataProvider authProvider
     */
    public function testDeleteWithProducts($user): void
    {
        $this->$user->givePermissionTo('product_sets.remove');

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

        $response = $this->actingAs($this->$user)->delete(
            '/product-sets/id:' . $newSet->getKey(),
        );
        $response->assertNoContent();
        $this->assertSoftDeleted($newSet);

        Event::assertDispatched(ProductSetDeleted::class);
    }

    /**
     * @dataProvider authProvider
     */
    public function testDeleteWithSubsets($user): void
    {
        $this->$user->givePermissionTo('product_sets.remove');

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

        $response = $this->actingAs($this->$user)->delete(
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
    public function testReorderRoot($user): void
    {
        $this->$user->givePermissionTo('product_sets.edit');

        $set1 = ProductSet::factory()->create();
        $set2 = ProductSet::factory()->create();
        $set3 = ProductSet::factory()->create();

        $response = $this->actingAs($this->$user)->postJson('/product-sets/reorder', [
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
    public function testReorderChildren($user): void
    {
        $this->$user->givePermissionTo('product_sets.edit');

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

        $response = $this->actingAs($this->$user)->postJson('/product-sets/reorder/id:' . $parent->getKey(), [
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
    public function testAttachProducts($user): void
    {
        $this->$user->givePermissionTo('product_sets.edit');

        $set = ProductSet::factory()->create();

        $product1 = Product::factory()->create();
        $product2 = Product::factory()->create();
        $product3 = Product::factory()->create();

        $response = $this->actingAs($this->$user)->postJson(
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
    public function testDetachProducts($user): void
    {
        $this->$user->givePermissionTo('product_sets.edit');

        $set = ProductSet::factory()->create();

        $product1 = Product::factory()->create();
        $product2 = Product::factory()->create();
        $product3 = Product::factory()->create();

        $set->products()->sync([
            $product1->getKey(),
            $product2->getKey(),
            $product3->getKey(),
        ]);

        $response = $this->actingAs($this->$user)->postJson(
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
    public function testShowProductsUnauthorized($user): void
    {
        $set = ProductSet::factory()->create([
            'public' => true,
        ]);

        $response = $this->actingAs($this->$user)->getJson(
            '/product-sets/id:' . $set->getKey() . '/products',
        );

        $response->assertForbidden();
    }

    /**
     * @dataProvider authProvider
     */
    public function testShowProducts($user): void
    {
        $this->$user->givePermissionTo('product_sets.show_details');

        $set = ProductSet::factory()->create([
            'public' => true,
        ]);

        $product1 = Product::factory()->create([
            'public' => true,
        ]);
        $product2 = Product::factory()->create([
            'public' => false,
        ]);
        $productNotInSet = Product::factory()->create([
            'public' => true,
        ]);

        $set->products()->sync([
            $product1->getKey(),
            $product2->getKey(),
        ]);

        $response = $this->actingAs($this->$user)
            ->getJson('/product-sets/id:' . $set->getKey() . '/products');

        $response
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJson(['data' => [
                [
                    'id' => $product1->getKey(),
                    'name' => $product1->name,
                    'slug' => $product1->slug,
                ]
            ]]);
    }

    /**
     * @dataProvider authProvider
     */
    public function testShowProductsHidden($user): void
    {
        $this->$user->givePermissionTo(['product_sets.show_details', 'product_sets.show_hidden']);

        $set = ProductSet::factory()->create([
            'public' => true,
        ]);

        $product1 = Product::factory()->create([
            'public' => true,
        ]);
        $product2 = Product::factory()->create([
            'public' => false,
        ]);

        $set->products()->sync([
            $product1->getKey(),
            $product2->getKey(),
        ]);

        $response = $this->actingAs($this->$user)->getJson(
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
}
