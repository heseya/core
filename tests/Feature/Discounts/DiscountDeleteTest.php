<?php

namespace Tests\Feature\Discounts;

use App\Enums\DiscountTargetType;
use App\Enums\DiscountType;
use App\Events\CouponDeleted;
use App\Events\SaleDeleted;
use App\Listeners\WebHookEventListener;
use App\Models\Discount;
use App\Models\Product;
use App\Models\ProductSet;
use App\Models\WebHook;
use App\Services\Contracts\DiscountServiceContract;
use Illuminate\Events\CallQueuedListener;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;
use Spatie\WebhookServer\CallWebhookJob;
use Tests\TestCase;

class DiscountDeleteTest extends TestCase
{
    /**
     * @dataProvider couponOrSaleProvider
     */
    public function testDeleteUnauthorized(string $discountKind): void
    {
        $code = $discountKind === 'coupons' ? [] : ['code' => null];
        $discount = Discount::factory($code)->create();

        Event::fake();

        $this
            ->deleteJson("/{$discountKind}/id:" . $discount->getKey())
            ->assertForbidden();

        $event = $discountKind === 'coupons' ? CouponDeleted::class : SaleDeleted::class;
        Event::assertNotDispatched($event);
    }

    /**
     * @dataProvider authWithDiscountProvider
     */
    public function testDeleteInvalidDiscount(string $user, string $discountKind): void
    {
        $this->{$user}->givePermissionTo("{$discountKind}.remove");

        $code = $discountKind === 'sales' ? [] : ['code' => null];
        $discount = Discount::factory($code)->create();

        Event::fake();

        $this
            ->actingAs($this->{$user})
            ->deleteJson("/{$discountKind}/id:" . $discount->getKey())
            ->assertNotFound();

        $event = $discountKind === 'coupons' ? CouponDeleted::class : SaleDeleted::class;
        Event::assertNotDispatched($event);
    }

    /**
     * @dataProvider authWithDiscountProvider
     */
    public function testDelete(string $user, string $discountKind): void
    {
        $this->{$user}->givePermissionTo("{$discountKind}.remove");
        $code = $discountKind === 'coupons' ? [] : ['code' => null];
        $discount = Discount::factory($code)->create();

        Queue::fake();

        $response = $this->actingAs($this->{$user})->deleteJson("/{$discountKind}/id:" . $discount->getKey());
        $response->assertNoContent();
        $this->assertSoftDeleted($discount);

        Queue::assertPushed(CallQueuedListener::class, function ($job) {
            return $job->class === WebHookEventListener::class;
        });

        $event = $discountKind === 'coupons' ? new CouponDeleted($discount) : new SaleDeleted($discount);
        $listener = new WebHookEventListener();

        $listener->handle($event);

        Queue::assertNotPushed(CallWebhookJob::class);
    }

    /**
     * @dataProvider authWithDiscountProvider
     */
    public function testDeleteWithWebHookQueue(string $user, string $discountKind): void
    {
        $this->{$user}->givePermissionTo("{$discountKind}.remove");

        if ($discountKind === 'coupons') {
            $webHookEvent = 'CouponDeleted';
            $code = [];
        } else {
            $webHookEvent = 'SaleDeleted';
            $code = ['code' => null];
        }

        $discount = Discount::factory($code)->create();

        $webHook = WebHook::factory()->create([
            'events' => [
                $webHookEvent,
            ],
            'model_type' => $this->{$user}::class,
            'creator_id' => $this->{$user}->getKey(),
            'with_issuer' => false,
            'with_hidden' => false,
        ]);

        Queue::fake();

        $response = $this->actingAs($this->{$user})->deleteJson("/{$discountKind}/id:" . $discount->getKey());

        Queue::assertPushed(CallQueuedListener::class, function ($job) {
            return $job->class === WebHookEventListener::class;
        });

        $response->assertNoContent();
        $this->assertSoftDeleted($discount);

        $event = $discountKind === 'coupons' ? new CouponDeleted($discount) : new SaleDeleted($discount);
        $listener = new WebHookEventListener();

        $listener->handle($event);

        Queue::assertPushed(CallWebhookJob::class, function ($job) use ($webHook, $discount, $webHookEvent) {
            $payload = $job->payload;

            return $job->webhookUrl === $webHook->url
                && isset($job->headers['Signature'])
                && $payload['data']['id'] === $discount->getKey()
                && $payload['data_type'] === 'Discount'
                && $payload['event'] === $webHookEvent;
        });
    }

    /**
     * @dataProvider authWithDiscountProvider
     */
    public function testDeleteWithWebHookDispatched(string $user, string $discountKind): void
    {
        $this->{$user}->givePermissionTo("{$discountKind}.remove");

        if ($discountKind === 'coupons') {
            $webHookEvent = 'CouponDeleted';
            $code = [];
        } else {
            $webHookEvent = 'SaleDeleted';
            $code = ['code' => null];
        }

        $discount = Discount::factory($code)->create();

        $webHook = WebHook::factory()->create([
            'events' => [
                $webHookEvent,
            ],
            'model_type' => $this->{$user}::class,
            'creator_id' => $this->{$user}->getKey(),
            'with_issuer' => false,
            'with_hidden' => false,
        ]);

        Bus::fake();

        $response = $this->actingAs($this->{$user})->deleteJson("/{$discountKind}/id:" . $discount->getKey());

        Bus::assertDispatched(CallQueuedListener::class, function ($job) {
            return $job->class === WebHookEventListener::class;
        });

        $response->assertNoContent();
        $this->assertSoftDeleted($discount);

        $event = $discountKind === 'coupons' ? new CouponDeleted($discount) : new SaleDeleted($discount);
        $listener = new WebHookEventListener();

        $listener->handle($event);

        Bus::assertDispatched(CallWebhookJob::class, function ($job) use ($webHook, $discount, $webHookEvent) {
            $payload = $job->payload;

            return $job->webhookUrl === $webHook->url
                && isset($job->headers['Signature'])
                && $payload['data']['id'] === $discount->getKey()
                && $payload['data_type'] === 'Discount'
                && $payload['event'] === $webHookEvent;
        });
    }

    /**
     * @dataProvider authProvider
     */
    public function testDeleteSaleWithProduct(string $user): void
    {
        $this->{$user}->givePermissionTo('sales.remove');
        $discount = Discount::factory([
            'type' => DiscountType::AMOUNT,
            'value' => 10,
            'code' => null,
            'target_type' => DiscountTargetType::PRODUCTS,
            'target_is_allow_list' => true,
        ])->create();

        $product = Product::factory()->create([
            'public' => true,
            'price' => 100,
            'price_min_initial' => 100,
            'price_max_initial' => 200,
        ]);

        $discount->products()->attach($product);

        /** @var DiscountServiceContract $discountService */
        $discountService = App::make(DiscountServiceContract::class);

        // Apply discount to products before update
        $discountService->applyDiscountsOnProducts(Collection::make([$product]));

        $this->assertDatabaseHas('products', [
            'id' => $product->getKey(),
            'price_min' => 90,
            'price_max' => 190,
        ]);

        $response = $this->actingAs($this->{$user})->deleteJson('/sales/id:' . $discount->getKey());
        $response->assertNoContent();
        $this->assertSoftDeleted($discount);

        $this->assertDatabaseHas('products', [
            'id' => $product->getKey(),
            'price_min' => 100,
            'price_max' => 200,
        ]);
    }

    /**
     * @dataProvider authProvider
     */
    public function testDeleteSaleWithProductInChildSet(string $user): void
    {
        $this->{$user}->givePermissionTo('sales.remove');
        $discount = Discount::factory([
            'type' => DiscountType::AMOUNT,
            'value' => 10,
            'code' => null,
            'target_type' => DiscountTargetType::PRODUCTS,
            'target_is_allow_list' => true,
        ])->create();

        $product = Product::factory()->create([
            'public' => true,
            'price' => 100,
            'price_min_initial' => 100,
            'price_max_initial' => 200,
        ]);

        $parentSet = ProductSet::factory()->create(['public' => true]);
        $childSet = ProductSet::factory()->create([
            'public' => true,
            'public_parent' => true,
            'parent_id' => $parentSet->getKey(),
        ]);
        $subChildSet = ProductSet::factory()->create([
            'public' => true,
            'public_parent' => true,
            'parent_id' => $childSet->getKey(),
        ]);

        $product->sets()->sync([$subChildSet->getKey()]);

        $discount->productSets()->attach($parentSet);

        /** @var DiscountServiceContract $discountService */
        $discountService = App::make(DiscountServiceContract::class);

        // Apply discount to products before update
        $discountService->applyDiscountsOnProducts(Collection::make([$product]));

        $this->assertDatabaseHas('products', [
            'id' => $product->getKey(),
            'price_min' => 90,
            'price_max' => 190,
        ]);

        $response = $this->actingAs($this->{$user})->deleteJson('/sales/id:' . $discount->getKey());
        $response->assertNoContent();
        $this->assertSoftDeleted($discount);

        $this->assertDatabaseHas('products', [
            'id' => $product->getKey(),
            'price_min' => 100,
            'price_max' => 200,
        ]);
    }
}
