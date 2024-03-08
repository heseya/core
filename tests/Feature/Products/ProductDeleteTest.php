<?php

namespace Tests\Feature\Products;

use App\Enums\MediaType;
use App\Events\ProductDeleted;
use App\Listeners\WebHookEventListener;
use App\Models\Media;
use App\Models\Product;
use App\Models\Schema;
use App\Models\WebHook;
use App\Services\ProductService;
use App\Services\SchemaCrudService;
use Domain\Currency\Currency;
use Domain\Price\Enums\ProductPriceType;
use Domain\Seo\Models\SeoMetadata;
use Illuminate\Events\CallQueuedListener;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Spatie\WebhookServer\CallWebhookJob;
use Tests\Utils\FakeDto;

class ProductDeleteTest extends ProductTestCase
{
    private SchemaCrudService $schemaCrudService;
    private ProductService $productService;

    public function setUp(): void
    {
        parent::setUp();

        $this->schemaCrudService = App::make(SchemaCrudService::class);
        $this->productService = App::make(ProductService::class);
    }

    /**
     * @dataProvider authProvider
     */
    public function testDeleteSchemaMinMaxPrice(string $user): void
    {
        $this->{$user}->givePermissionTo('schemas.remove');

        $schemaPrice = 50;
        $schema = $this->schemaCrudService->store(
            FakeDto::schemaDto([
                'type' => 0,
                'required' => true,
                'prices' => [['value' => $schemaPrice, 'currency' => Currency::DEFAULT->value]],
            ])
        );

        $this->product->schemas()->attach($schema->getKey());

        $this->productService->updateMinMaxPrices($this->product);

        $response = $this->actingAs($this->{$user})->deleteJson('/schemas/id:' . $schema->getKey());
        $response->assertNoContent();

        $this->assertDatabaseHas('prices', [
            'model_id' => $this->product->getKey(),
            'price_type' => ProductPriceType::PRICE_BASE->value,
            'value' => 100 * 100,
            'currency' => $this->currency->value,
        ]);
        $this->assertDatabaseHas('prices', [
            'model_id' => $this->product->getKey(),
            'price_type' => ProductPriceType::PRICE_MIN->value,
            'value' => 100 * 100,
            'currency' => $this->currency->value,
        ]);
        $this->assertDatabaseHas('prices', [
            'model_id' => $this->product->getKey(),
            'price_type' => ProductPriceType::PRICE_MAX->value,
            'value' => 100 * 100,
            'currency' => $this->currency->value,
        ]);
    }

    public function testDeleteUnauthorized(): void
    {
        Event::fake(ProductDeleted::class);
        $this->deleteJson('/products/id:' . $this->product->getKey())
            ->assertForbidden();
        Event::assertNotDispatched(ProductDeleted::class);
    }

    /**
     * @dataProvider authProvider
     */
    public function testDelete(string $user): void
    {
        $this->{$user}->givePermissionTo('products.remove');

        Queue::fake();
        $product = Product::factory([
            'name' => 'Created',
            'slug' => 'created',
            'description_html' => '<h1>Description</h1>',
            'public' => false,
        ])->create();

        $seo = SeoMetadata::factory()->create();
        $product->seo()->save($seo);

        $response = $this->actingAs($this->{$user})
            ->deleteJson('/products/id:' . $product->getKey());
        $response->assertNoContent();
        $this->assertSoftDeleted($product);
        $this->assertSoftDeleted($seo);

        Queue::assertPushed(CallQueuedListener::class, function ($job) {
            return $job->class === WebHookEventListener::class
                && $job->data[0] instanceof ProductDeleted;
        });

        $event = new ProductDeleted($this->product);
        $listener = new WebHookEventListener();

        $listener->handle($event);

        Queue::assertNotPushed(CallWebhookJob::class);
    }

    /**
     * @dataProvider authProvider
     */
    public function testDeleteWithMedia(string $user): void
    {
        $this->{$user}->givePermissionTo('products.remove');

        $media = Media::factory()->create([
            'type' => MediaType::PHOTO,
            'url' => 'https://picsum.photos/seed/' . mt_rand(0, 999999) . '/800',
        ]);

        $product = Product::factory([
            'name' => 'Delete with media',
            'slug' => 'Delete-with-media',
            'description_html' => '<h1>Description</h1>',
            'public' => false,
        ])->create();

        $product->media()->sync($media);

        Http::fake(['*' => Http::response(status: 204)]);

        $response = $this->actingAs($this->{$user})
            ->deleteJson('/products/id:' . $product->getKey());
        $response->assertNoContent();
        $this->assertSoftDeleted($product);
        $this->assertModelMissing($media);
    }

    /**
     * @dataProvider authProvider
     */
    public function testDeleteWithWebHookQueue(string $user): void
    {
        $this->{$user}->givePermissionTo('products.remove');

        $webHook = WebHook::factory()->create([
            'events' => [
                'ProductDeleted',
            ],
            'model_type' => $this->{$user}::class,
            'creator_id' => $this->{$user}->getKey(),
            'with_issuer' => false,
            'with_hidden' => false,
        ]);

        Queue::fake();

        $response = $this->actingAs($this->{$user})
            ->deleteJson('/products/id:' . $this->product->getKey());
        $response->assertNoContent();

        Queue::assertPushed(CallQueuedListener::class, function ($job) {
            return $job->class === WebHookEventListener::class
                && $job->data[0] instanceof ProductDeleted;
        });

        $this->assertSoftDeleted($this->product);

        $product = $this->product;
        $event = new ProductDeleted($product);
        $listener = new WebHookEventListener();

        $listener->handle($event);

        Queue::assertPushed(CallWebhookJob::class, function ($job) use ($webHook, $product) {
            $payload = $job->payload;

            return $job->webhookUrl === $webHook->url
                && isset($job->headers['Signature'])
                && $payload['data']['id'] === $product->getKey()
                && $payload['data_type'] === 'Product'
                && $payload['event'] === 'ProductDeleted';
        });
    }

    /**
     * @dataProvider authProvider
     */
    public function testDeleteWithWebHookDispatched(string $user): void
    {
        $this->{$user}->givePermissionTo('products.remove');

        $webHook = WebHook::factory()->create([
            'events' => [
                'ProductDeleted',
            ],
            'model_type' => $this->{$user}::class,
            'creator_id' => $this->{$user}->getKey(),
            'with_issuer' => false,
            'with_hidden' => false,
        ]);

        Bus::fake();

        $response = $this->actingAs($this->{$user})
            ->deleteJson('/products/id:' . $this->product->getKey());
        $response->assertNoContent();

        Bus::assertDispatched(CallQueuedListener::class, function ($job) {
            return $job->class === WebHookEventListener::class
                && $job->data[0] instanceof ProductDeleted;
        });

        $this->assertSoftDeleted($this->product);

        $product = $this->product;
        $event = new ProductDeleted($product);
        $listener = new WebHookEventListener();

        $listener->handle($event);

        Bus::assertDispatched(CallWebhookJob::class, function ($job) use ($webHook, $product) {
            $payload = $job->payload;

            return $job->webhookUrl === $webHook->url
                && isset($job->headers['Signature'])
                && $payload['data']['id'] === $product->getKey()
                && $payload['data_type'] === 'Product'
                && $payload['event'] === 'ProductDeleted';
        });
    }
}
