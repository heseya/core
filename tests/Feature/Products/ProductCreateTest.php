<?php

namespace Tests\Feature\Products;

use App\Enums\DiscountTargetType;
use App\Enums\ExceptionsEnums\Exceptions;
use App\Enums\MediaType;
use App\Enums\SchemaType;
use App\Events\ProductCreated;
use App\Events\ProductPriceUpdated;
use App\Listeners\WebHookEventListener;
use App\Models\Discount;
use App\Models\Media;
use App\Models\Product;
use App\Models\ProductAttribute;
use App\Models\Schema;
use App\Models\WebHook;
use App\Repositories\Contracts\ProductRepositoryContract;
use App\Repositories\DiscountRepository;
use App\Services\SchemaCrudService;
use Domain\Currency\Currency;
use Domain\Language\Language;
use Domain\Page\Page;
use Domain\Price\Dtos\PriceDto;
use Domain\Price\Enums\ProductPriceType;
use Domain\ProductAttribute\Enums\AttributeType;
use Domain\ProductAttribute\Models\Attribute;
use Domain\ProductAttribute\Models\AttributeOption;
use Domain\ProductSet\ProductSet;
use Illuminate\Events\CallQueuedListener;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;
use Ramsey\Uuid\Uuid;
use Spatie\WebhookServer\CallWebhookJob;
use Tests\TestCase;
use Tests\Utils\FakeDto;

class ProductCreateTest extends TestCase
{
    private array $productPrices;
    private DiscountRepository $discountRepository;
    private Currency $currency;
    private SchemaCrudService $schemaCrudService;

    public function setUp(): void
    {
        parent::setUp();

        $this->productPrices = array_map(fn (Currency $currency) => [
            'value' => '100.00',
            'currency' => $currency->value,
        ], Currency::cases());

        $this->discountRepository = App::make(DiscountRepository::class);

        $this->currency = Currency::DEFAULT;

        $this->schemaCrudService = App::make(SchemaCrudService::class);
    }

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
                'translations' => [
                    $this->lang => [
                        'name' => 'Test',
                    ],
                ],
                'published' => [$this->lang],
                'slug' => 'slug',
                'prices_base' => $this->productPrices,
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
    public function testCreateProductBanner(string $user): void
    {
        $this->{$user}->givePermissionTo('products.add');

        $media = Media::factory()->create();

        $this
            ->actingAs($this->{$user})
            ->json('POST', '/products', [
                'translations' => [
                    $this->lang => [
                        'name' => 'Test',
                    ],
                ],
                'published' => [$this->lang],
                'slug' => 'slug',
                'prices_base' => $this->productPrices,
                'public' => true,
                'shipping_digital' => false,
                'banner' => [
                    'url' => 'http://example.com',
                    'translations' => [
                        $this->lang => [
                            'title' => 'banner title',
                            'subtitle' => 'banner subtitle',
                        ],
                    ],
                    'media' => [
                        [
                            'min_screen_width' => 1024,
                            'media' => $media->getKey(),
                        ],
                    ],
                ],
            ])
            ->assertCreated()
            ->assertJsonFragment([
                'url' => 'http://example.com',
                'title' => 'banner title',
                'subtitle' => 'banner subtitle',
            ])
            ->assertJsonFragment([
                'min_screen_width' => 1024,
            ]);

        $this->assertDatabaseHas('product_banner_media', [
            "title->{$this->lang}" => 'banner title',
            "subtitle->{$this->lang}" => 'banner subtitle',
        ]);
        $this->assertDatabaseCount('product_banner_responsive_media', 1);
    }

    /**
     * @dataProvider authProvider
     */
    public function testCreateProductBannerNull(string $user): void
    {
        $this->{$user}->givePermissionTo('products.add');

        $this
            ->actingAs($this->{$user})
            ->json('POST', '/products', [
                'translations' => [
                    $this->lang => [
                        'name' => 'Test',
                    ],
                ],
                'published' => [$this->lang],
                'slug' => 'slug',
                'prices_base' => $this->productPrices,
                'public' => true,
                'shipping_digital' => false,
                'banner' => null,
            ])
            ->assertCreated()
            ->assertJsonFragment([
                'banner' => null,
            ]);
    }

    /**
     * @dataProvider authProvider
     */
    public function testCreateProductBannerNoTitle(string $user): void
    {
        $this->{$user}->givePermissionTo('products.add');

        $media = Media::factory()->create();

        $this
            ->actingAs($this->{$user})
            ->json('POST', '/products', [
                'translations' => [
                    $this->lang => [
                        'name' => 'Test',
                    ],
                ],
                'published' => [$this->lang],
                'slug' => 'slug',
                'prices_base' => $this->productPrices,
                'public' => true,
                'shipping_digital' => false,
                'banner' => [
                    "translations" => [
                        $this->lang => [
                            'title' => null,
                            'name' => null,
                        ]
                    ],
                    'media' => [
                        [
                            'min_screen_width' => 1024,
                            'media' => $media->getKey(),
                        ],
                    ],
                ],
            ])
            ->assertCreated()
            ->assertJsonFragment([
                'min_screen_width' => 1024,
            ]);

        $this->assertDatabaseCount('product_banner_responsive_media', 1);
    }

    public function testCreateUnauthorized(): void
    {
        Event::fake([ProductCreated::class]);
        $this->postJson('/products')->assertForbidden();
        Event::assertNotDispatched(ProductCreated::class);
    }

    /**
     * @dataProvider authProvider
     */
    public function testCreate(string $user): void
    {
        $this->{$user}->givePermissionTo('products.add');

        Queue::fake();

        $response = $this->actingAs($this->{$user})->postJson('/products', [
            'slug' => 'test',
            'prices_base' => $this->productPrices,
            'public' => true,
            'shipping_digital' => false,
            'translations' => [
                $this->lang => [
                    'name' => 'Test',
                    'description_html' => '<h1>Description</h1>',
                    'description_short' => 'So called short description...',
                ],
            ],
            'published' => [$this->lang],
        ]);

        $response
            ->assertCreated()
            ->assertJson([
                'data' => [
                    'slug' => 'test',
                    'name' => 'Test',
                    'public' => true,
                    'shipping_digital' => false,
                    'description_html' => '<h1>Description</h1>',
                    'description_short' => 'So called short description...',
                    'cover' => null,
                    'gallery' => [],
                ],
            ])
            ->assertJsonFragment([
                'gross' => '100.00',
                'currency' => $this->currency->value,
            ]);

        $this->assertDatabaseHas('products', [
            'slug' => 'test',
            "name->{$this->lang}" => 'Test',
            'public' => true,
            'shipping_digital' => false,
            "description_html->{$this->lang}" => '<h1>Description</h1>',
            "description_short->{$this->lang}" => 'So called short description...',
        ]);

        Queue::assertPushed(CallQueuedListener::class, function ($job) {
            return $job->class === WebHookEventListener::class
                && $job->data[0] instanceof ProductCreated;
        });

        /** @var Product $product */
        $product = Product::query()->find($response->json('data.id'));
        $event = new ProductCreated($product);
        $listener = new WebHookEventListener();

        $listener->handle($event);

        Queue::assertNotPushed(CallWebhookJob::class);
    }

    public function testCreatePriceUpdate(): array
    {
        $this->user->givePermissionTo('products.add');

        Event::fake(ProductPriceUpdated::class);

        $response = $this->actingAs($this->user)->postJson('/products', [
            'slug' => 'test',
            'prices_base' => $this->productPrices,
            'public' => true,
            'shipping_digital' => false,
            'translations' => [
                $this->lang => [
                    'name' => 'Test',
                    'description_html' => '<h1>Description</h1>',
                    'description_short' => 'So called short description...',
                ],
            ],
            'published' => [$this->lang],
        ])
            ->assertCreated();

        /** @var Product $product */
        $product = Product::where('id', $response->getData()->data->id)->first();

        Event::assertDispatched(ProductPriceUpdated::class);

        $productPrices = app(ProductRepositoryContract::class)->getProductPrices($product->getKey(), [
            ProductPriceType::PRICE_MIN,
            ProductPriceType::PRICE_MAX,
        ]);

        $productPricesMin = $productPrices->get(ProductPriceType::PRICE_MIN->value);
        $productPricesMax = $productPrices->get(ProductPriceType::PRICE_MAX->value);

        return [
            $product,
            new ProductPriceUpdated(
                $product->getKey(),
                null,
                null,
                $productPricesMin->toArray(),
                $productPricesMax->toArray()
            ),
        ];
    }

    /**
     * @depends testCreatePriceUpdate
     */
    public function testCreateProductPriceUpdateWebhookDispatch($payload): void
    {
        $webHook = WebHook::factory()->create([
            'events' => [
                'ProductPriceUpdated',
            ],
            'model_type' => $this->user::class,
            'creator_id' => $this->user->getKey(),
            'with_issuer' => false,
            'with_hidden' => false,
        ]);

        Bus::fake();

        [$product, $event] = $payload;

        $listener = new WebHookEventListener();

        $listener->handle($event);

        Bus::assertDispatched(CallWebhookJob::class, function ($job) use ($webHook, $product) {
            $payload = $job->payload;

            return $job->webhookUrl === $webHook->url
                && isset($job->headers['Signature'])
                && $payload['data']['id'] === $product->getKey()
                && isset($payload['data']['prices_min_old'])
                && isset($payload['data']['prices_max_old'])
                && isset($payload['data']['prices_min_new'])
                && isset($payload['data']['prices_max_new'])
                && $payload['data_type'] === 'ProductPrices'
                && $payload['event'] === 'ProductPriceUpdated';
        });
    }

    /**
     * @dataProvider authProvider
     */
    public function testCreateWithWebHookQueue(string $user): void
    {
        $this->{$user}->givePermissionTo('products.add');

        $webHook = WebHook::factory()->create([
            'events' => [
                'ProductCreated',
            ],
            'model_type' => $this->{$user}::class,
            'creator_id' => $this->{$user}->getKey(),
            'with_issuer' => false,
            'with_hidden' => false,
        ]);

        Queue::fake();

        $response = $this->actingAs($this->{$user})->postJson('/products', [
            'translations' => [
                $this->lang => [
                    'name' => 'Test',
                    'description_html' => '<h1>Description</h1>',
                ],
            ],
            'published' => [$this->lang],
            'slug' => 'test',
            'prices_base' => $this->productPrices,
            'public' => true,
            'shipping_digital' => false,
        ]);

        $response
            ->assertCreated()
            ->assertJson([
                'data' => [
                    'slug' => 'test',
                    'name' => 'Test',
                    'public' => true,
                    'shipping_digital' => false,
                    'description_html' => '<h1>Description</h1>',
                    'cover' => null,
                    'gallery' => [],
                ],
            ]);

        $this->assertDatabaseHas('products', [
            'slug' => 'test',
            "name->{$this->lang}" => 'Test',
            'public' => true,
            'shipping_digital' => false,
            "description_html->{$this->lang}" => '<h1>Description</h1>',
        ]);

        Queue::assertPushed(CallQueuedListener::class, function ($job) {
            return $job->class === WebHookEventListener::class
                && $job->data[0] instanceof ProductCreated;
        });

        /** @var Product $product */
        $product = Product::query()->find($response->json('data.id'));
        $event = new ProductCreated($product);
        $listener = new WebHookEventListener();

        $listener->handle($event);

        Queue::assertPushed(CallWebhookJob::class, function ($job) use ($webHook, $product) {
            $payload = $job->payload;

            return $job->webhookUrl === $webHook->url
                && isset($job->headers['Signature'])
                && $payload['data']['id'] === $product->getKey()
                && $payload['data_type'] === 'Product'
                && $payload['event'] === 'ProductCreated';
        });
    }

    /**
     * @dataProvider authProvider
     */
    public function testCreateWithWebHookDispatched(string $user): void
    {
        $this->{$user}->givePermissionTo('products.add');

        $webHook = WebHook::factory()->create([
            'events' => [
                'ProductCreated',
            ],
            'model_type' => $this->{$user}::class,
            'creator_id' => $this->{$user}->getKey(),
            'with_issuer' => false,
            'with_hidden' => false,
        ]);

        Bus::fake();

        $response = $this->actingAs($this->{$user})->postJson('/products', [
            'translations' => [
                $this->lang => [
                    'name' => 'Test',
                    'description_html' => '<h1>Description</h1>',
                ],
            ],
            'published' => [$this->lang],
            'slug' => 'test',
            'prices_base' => $this->productPrices,
            'public' => true,
            'shipping_digital' => false,
        ]);

        $response
            ->assertCreated()
            ->assertJson([
                'data' => [
                    'slug' => 'test',
                    'name' => 'Test',
                    'public' => true,
                    'shipping_digital' => false,
                    'description_html' => '<h1>Description</h1>',
                    'cover' => null,
                    'gallery' => [],
                ],
            ]);

        $this->assertDatabaseHas('products', [
            'slug' => 'test',
            "name->{$this->lang}" => 'Test',
            'public' => true,
            'shipping_digital' => false,
            "description_html->{$this->lang}" => '<h1>Description</h1>',
        ]);

        Bus::assertDispatched(CallQueuedListener::class, function ($job) {
            return $job->class === WebHookEventListener::class
                && $job->data[0] instanceof ProductCreated;
        });

        /** @var Product $product */
        $product = Product::query()->find($response->json('data.id'));
        $event = new ProductCreated($product);
        $listener = new WebHookEventListener();

        $listener->handle($event);

        Bus::assertDispatched(CallWebhookJob::class, function ($job) use ($webHook, $product) {
            $payload = $job->payload;

            return $job->webhookUrl === $webHook->url
                && isset($job->headers['Signature'])
                && $payload['data']['id'] === $product->getKey()
                && $payload['data_type'] === 'Product'
                && $payload['event'] === 'ProductCreated';
        });
    }

    /**
     * @dataProvider authProvider
     */
    public function testCreateHiddenWithWebHookWithoutHidden(string $user): void
    {
        $this->{$user}->givePermissionTo('products.add');

        WebHook::factory()->create([
            'events' => [
                'ProductCreated',
            ],
            'model_type' => $this->{$user}::class,
            'creator_id' => $this->{$user}->getKey(),
            'with_issuer' => false,
            'with_hidden' => false,
        ]);

        Queue::fake();

        $response = $this->actingAs($this->{$user})->postJson('/products', [
            'translations' => [
                $this->lang => [
                    'name' => 'Test',
                    'description_html' => '<h1>Description</h1>',
                ],
            ],
            'published' => [$this->lang],
            'slug' => 'test',
            'prices_base' => $this->productPrices,
            'public' => false,
            'shipping_digital' => false,
        ]);

        $response
            ->assertCreated()
            ->assertJsonFragment([
                'slug' => 'test',
                'name' => 'Test',
                'public' => false,
                'shipping_digital' => false,
                'description_html' => '<h1>Description</h1>',
                'cover' => null,
                'gallery' => [],
            ]);

        $this->assertDatabaseHas('products', [
            'slug' => 'test',
            "name->{$this->lang}" => 'Test',
            'public' => false,
            'shipping_digital' => false,
            "description_html->{$this->lang}" => '<h1>Description</h1>',
        ]);

        Queue::assertPushed(CallQueuedListener::class, function ($job) {
            return $job->class === WebHookEventListener::class
                && $job->data[0] instanceof ProductCreated;
        });

        /** @var Product $product */
        $product = Product::query()->find($response->json('data.id'));
        $event = new ProductCreated($product);
        $listener = new WebHookEventListener();

        $listener->handle($event);

        Queue::assertNotPushed(CallWebhookJob::class);
    }

    /**
     * @dataProvider authProvider
     */
    public function testCreateHiddenWithWebHook(string $user): void
    {
        $this->{$user}->givePermissionTo('products.add');

        $webHook = WebHook::factory()->create([
            'events' => [
                'ProductCreated',
            ],
            'model_type' => $this->{$user}::class,
            'creator_id' => $this->{$user}->getKey(),
            'with_issuer' => false,
            'with_hidden' => true,
        ]);

        Bus::fake();

        $response = $this->actingAs($this->{$user})->postJson('/products', [
            'translations' => [
                $this->lang => [
                    'name' => 'Test',
                    'description_html' => '<h1>Description</h1>',
                ],
            ],
            'published' => [$this->lang],
            'slug' => 'test',
            'prices_base' => $this->productPrices,
            'public' => false,
            'shipping_digital' => false,
        ]);

        $response
            ->assertCreated()
            ->assertJson([
                'data' => [
                    'slug' => 'test',
                    'name' => 'Test',
                    'public' => false,
                    'shipping_digital' => false,
                    'description_html' => '<h1>Description</h1>',
                    'cover' => null,
                    'gallery' => [],
                ],
            ]);

        $this->assertDatabaseHas('products', [
            'slug' => 'test',
            "name->{$this->lang}" => 'Test',
            'public' => false,
            'shipping_digital' => false,
            "description_html->{$this->lang}" => '<h1>Description</h1>',
        ]);

        Bus::assertDispatched(CallQueuedListener::class, fn ($job) => $job->class = WebHookEventListener::class);

        /** @var Product $product */
        $product = Product::query()->find($response->json('data.id'));
        $event = new ProductCreated($product);
        $listener = new WebHookEventListener();

        $listener->handle($event);

        Bus::assertDispatched(CallWebhookJob::class, function ($job) use ($webHook, $product) {
            $payload = $job->payload;

            return $job->webhookUrl === $webHook->url
                && isset($job->headers['Signature'])
                && $payload['data']['id'] === $product->getKey()
                && $payload['data_type'] === 'Product'
                && $payload['event'] === 'ProductCreated';
        });
    }

    /**
     * @dataProvider authProvider
     */
    public function testCreateWithUuid(string $user): void
    {
        $this->{$user}->givePermissionTo('products.add');

        $uuid = Uuid::uuid4()->toString();

        $this
            ->actingAs($this->{$user})
            ->postJson('/products', [
                'id' => $uuid,
                'translations' => [
                    $this->lang => [
                        'name' => 'Test',
                    ],
                ],
                'published' => [$this->lang],
                'slug' => 'test',
                'prices_base' => $this->productPrices,
                'public' => true,
                'shipping_digital' => false,
            ])
            ->assertCreated()
            ->assertJson([
                'data' => [
                    'id' => $uuid,
                    'slug' => 'test',
                    'name' => 'Test',
                    'public' => true,
                    'shipping_digital' => false,
                ],
            ]);

        $this->assertDatabaseHas('products', [
            'id' => $uuid,
            'slug' => 'test',
            "name->{$this->lang}" => 'Test',
            'shipping_digital' => false,
        ]);
    }

    /**
     * @dataProvider authProvider
     */
    public function testCreateDigital(string $user): void
    {
        $this->{$user}->givePermissionTo('products.add');

        $this
            ->actingAs($this->{$user})
            ->postJson('/products', [
                'translations' => [
                    $this->lang => [
                        'name' => 'Test',
                    ],
                ],
                'published' => [$this->lang],
                'slug' => 'test',
                'prices_base' => $this->productPrices,
                'public' => true,
                'shipping_digital' => true,
            ])
            ->assertCreated()
            ->assertJsonFragment([
                'slug' => 'test',
                'name' => 'Test',
                'public' => true,
                'shipping_digital' => true,
            ]);

        $this->assertDatabaseHas('products', [
            'slug' => 'test',
            "name->{$this->lang}" => 'Test',
            'shipping_digital' => true,
        ]);
    }

    /**
     * @dataProvider authProvider
     */
    public function testCreateWithSchemas(string $user): void
    {
        $this->{$user}->givePermissionTo('products.add');

        Event::fake([ProductCreated::class]);

        /** @var Schema $schema */
        $schema = $this->schemaCrudService->store(FakeDto::schemaDto());

        $response = $this->actingAs($this->{$user})->postJson('/products', [
            'translations' => [
                $this->lang => [
                    'name' => 'Test',
                ],
            ],
            'published' => [$this->lang],
            'slug' => 'test',
            'prices_base' => $this->productPrices,
            'public' => false,
            'shipping_digital' => false,
            'schemas' => [
                $schema->getKey(),
            ],
        ]);
        $response->assertCreated();

        $this->assertDatabaseHas('products', [
            'slug' => 'test',
            "name->{$this->lang}" => 'Test',
            'public' => false,
            'shipping_digital' => false,
            'description_html' => null,
        ]);

        $this->assertDatabaseHas('product_schemas', [
            'product_id' => $response->json('data.id'),
            'schema_id' => $schema->getKey(),
        ]);

        Event::assertDispatched(ProductCreated::class);
    }

    /**
     * @dataProvider authProvider
     */
    public function testCreateWithSets(string $user): void
    {
        $this->{$user}->givePermissionTo('products.add');

        Event::fake([ProductCreated::class]);

        $set1 = ProductSet::factory()->create();
        $set2 = ProductSet::factory()->create();

        $product = Product::factory()->create();
        $set2->products()->attach($product->getKey());
        $set2->descendantProducts()->attach([
            $product->getKey() => ['order' => 0],
        ]);

        $response = $this->actingAs($this->{$user})->postJson('/products', [
            'translations' => [
                $this->lang => [
                    'name' => 'Test',
                ],
            ],
            'published' => [$this->lang],
            'slug' => 'test',
            'prices_base' => $this->productPrices,
            'public' => false,
            'shipping_digital' => false,
            'sets' => [
                $set1->getKey(),
                $set2->getKey(),
            ],
        ]);

        $response->assertCreated();
        $productId = $response->json('data.id');

        $this->assertDatabaseHas('products', [
            'slug' => 'test',
            "name->{$this->lang}" => 'Test',
            'public' => false,
            'shipping_digital' => false,
            "description_html->{$this->lang}" => null,
        ]);

        $this->assertDatabaseHas('product_set_product', [
            'product_id' => $productId,
            'product_set_id' => $set1->getKey(),
        ]);

        $this->assertDatabaseHas('product_set_product', [
            'product_id' => $productId,
            'product_set_id' => $set2->getKey(),
        ]);

        $this->assertDatabaseHas('product_set_product_descendant', [
            'product_id' => $productId,
            'product_set_id' => $set1->getKey(),
            'order' => 0,
        ]);

        $this->assertDatabaseHas('product_set_product_descendant', [
            'product_id' => $productId,
            'product_set_id' => $set2->getKey(),
            'order' => 1,
        ]);

        Event::assertDispatched(ProductCreated::class);
    }

    /**
     * @dataProvider authWithTwoBooleansProvider
     */
    public function testCreateWithSeo(string $user, $boolean, $booleanValue): void
    {
        $this->{$user}->givePermissionTo('products.add');

        $media = Media::factory()->create([
            'type' => MediaType::PHOTO,
            'url' => 'https://picsum.photos/seed/' . mt_rand(0, 999999) . '/800',
        ]);

        $data = FakeDto::productCreateData([
            'translations' => [
                $this->lang => [
                    'name' => 'Test',
                    'description_html' => '<h1>Description</h1>',
                ],
            ],
            'published' => [$this->lang],
            'slug' => 'test',
            'prices_base' => $this->productPrices,
            'public' => $boolean,
            'shipping_digital' => false,
            'seo' => [
                'translations' => [
                    $this->lang => [
                        'title' => 'seo title',
                        'description' => 'seo description',
                        'no_index' => $booleanValue,
                    ],
                ],
                'og_image_id' => $media->getKey(),
                'no_index' => $boolean,
                'header_tags' => ['test1', 'test2'],
                'published' => [$this->lang],
            ],
        ]);

        $response = $this->actingAs($this->{$user})->json('POST', '/products?with_translations=1', $data);

        $response
            ->assertCreated()
            ->assertJsonFragment([
                'title' => 'seo title',
                'description' => 'seo description',
                'no_index' => $booleanValue,
            ]);

        $product = Product::query()->find($response->json('data.id'))->first();

        $this->assertDatabaseHas('seo_metadata', [
            "title->{$this->lang}" => 'seo title',
            "description->{$this->lang}" => 'seo description',
            'model_id' => $response->json('data.id'),
            'model_type' => $product->getMorphClass(),
            "no_index->{$this->lang}" => $booleanValue,
        ]);
    }

    /**
     * @dataProvider authProvider
     */
    public function testCreateMinMaxPrice(string $user): void
    {
        $this->{$user}->givePermissionTo('products.add');

        $schemaPrice = 50;

        $schema = $this->schemaCrudService->store(
            FakeDto::schemaDto([
                'type' => SchemaType::STRING,
                'required' => false,
                'prices' => [['value' => $schemaPrice, 'currency' => Currency::DEFAULT->value]],
            ])
        );

        $response = $this->actingAs($this->{$user})->postJson(
            '/products',
            FakeDto::productCreateData([
                'name' => 'Test',
                'slug' => 'test',
                'prices_base' => $this->productPrices,
                'public' => false,
                'shipping_digital' => false,
                'sets' => [],
                'schemas' => [
                    $schema->getKey(),
                ],
                'translations' => [
                    $this->lang => [
                        'name' => 'Test',
                    ],
                ],
                'published' => [$this->lang],
            ])
        );

        $response->assertCreated();

        $productId = $response->json('data.id');

        $this->assertDatabaseHas('prices', [
            'model_id' => $productId,
            'price_type' => ProductPriceType::PRICE_BASE,
            'value' => 100 * 100,
        ]);
        $this->assertDatabaseHas('prices', [
            'model_id' => $productId,
            'price_type' => ProductPriceType::PRICE_MIN,
            'value' => 100 * 100,
        ]);
        $this->assertDatabaseHas('prices', [
            'model_id' => $productId,
            'price_type' => ProductPriceType::PRICE_MAX,
            'value' => (100 + $schemaPrice) * 100,
        ]);
    }

    /**
     * @dataProvider authProvider
     */
    public function testCreateMinPriceWithRequiredSchema(string $user): void
    {
        $this->{$user}->givePermissionTo('products.add');

        $schemaPrice = 50;
        $schema = $this->schemaCrudService->store(
            FakeDto::schemaDto([
                'type' => SchemaType::STRING,
                'required' => true,
                'prices' => [['value' => $schemaPrice, 'currency' => Currency::DEFAULT->value]],
            ])
        );

        $response = $this->actingAs($this->{$user})->postJson('/products', [
            'translations' => [
                $this->lang => [
                    'name' => 'Test',
                ],
            ],
            'published' => [$this->lang],
            'slug' => 'test',
            'prices_base' => $this->productPrices,
            'public' => false,
            'shipping_digital' => false,
            'sets' => [],
            'schemas' => [
                $schema->getKey(),
            ],
        ]);

        $response->assertCreated();

        $productId = $response->json('data.id');

        $this->assertDatabaseHas('prices', [
            'model_id' => $productId,
            'price_type' => ProductPriceType::PRICE_BASE,
            'value' => 100 * 100,
        ]);
        $this->assertDatabaseHas('prices', [
            'model_id' => $productId,
            'price_type' => ProductPriceType::PRICE_MIN,
            'value' => (100 + $schemaPrice) * 100,
        ]);
        $this->assertDatabaseHas('prices', [
            'model_id' => $productId,
            'price_type' => ProductPriceType::PRICE_MAX,
            'value' => (100 + $schemaPrice) * 100,
        ]);
    }

    /**
     * @dataProvider authProvider
     */
    public function testCreateWithAttribute(string $user): void
    {
        $this->{$user}->givePermissionTo('products.add');

        $attribute = Attribute::factory()->create();

        $option = AttributeOption::factory()->create([
            'index' => 1,
            'attribute_id' => $attribute->getKey(),
        ]);

        $attribute2 = Attribute::factory()->create();

        $option2 = AttributeOption::factory()->create([
            'index' => 2,
            'attribute_id' => $attribute2->getKey(),
        ]);

        $response = $this
            ->actingAs($this->{$user})
            ->postJson('/products', [
                'translations' => [
                    $this->lang => [
                        'name' => 'Test',
                    ],
                ],
                'published' => [$this->lang],
                'slug' => 'test',
                'prices_base' => $this->productPrices,
                'public' => true,
                'shipping_digital' => false,
                'attributes' => [
                    $attribute->getKey() => [
                        $option->getKey(),
                    ],
                    $attribute2->getKey() => [
                        $option2->getKey(),
                    ],
                ],
            ])
            ->assertCreated()
            ->assertJsonFragment([
                'id' => $attribute->getKey(),
                'name' => $attribute->name,
                'slug' => $attribute->slug,
                'description' => $attribute->description,
                'type' => $attribute->type,
                'global' => $attribute->global,
                'sortable' => $attribute->sortable,
            ])
            ->assertJsonFragment([
                'id' => $option->getKey(),
                'name' => $option->name,
                'index' => $option->index,
                'value_number' => $option->value_number,
                'value_date' => $option->value_date,
                'attribute_id' => $attribute->getKey(),
            ])
            ->assertJsonFragment([
                'id' => $attribute2->getKey(),
                'name' => $attribute2->name,
                'slug' => $attribute2->slug,
                'description' => $attribute2->description,
                'type' => $attribute2->type,
                'global' => $attribute2->global,
                'sortable' => $attribute2->sortable,
            ])
            ->assertJsonFragment([
                'id' => $option2->getKey(),
                'name' => $option2->name,
                'index' => $option2->index,
                'value_number' => $option2->value_number,
                'value_date' => $option2->value_date,
                'attribute_id' => $attribute2->getKey(),
            ]);

        $this->assertDatabaseHas('products', [
            'slug' => 'test',
            "name->{$this->lang}" => 'Test',
        ]);

        /** @var Product $product */
        $product = Product::query()->find($response->json('data.id'));

        $productAttribute1 = ProductAttribute::where('product_id', $product->getKey())
            ->where('attribute_id', $attribute->getKey())
            ->first();
        $productAttribute2 = ProductAttribute::where('product_id', $product->getKey())
            ->where('attribute_id', $attribute2->getKey())
            ->first();

        $this->assertDatabaseHas('product_attribute', [
            'product_id' => $product->getKey(),
            'attribute_id' => $attribute->getKey(),
        ]);
        $this->assertDatabaseHas('product_attribute', [
            'product_id' => $product->getKey(),
            'attribute_id' => $attribute2->getKey(),
        ]);

        $this->assertDatabaseHas('product_attribute_attribute_option', [
            'product_attribute_id' => $productAttribute1->getKey(),
            'attribute_option_id' => $option->getKey(),
        ]);

        $this->assertDatabaseHas('product_attribute_attribute_option', [
            'product_attribute_id' => $productAttribute2->getKey(),
            'attribute_option_id' => $option2->getKey(),
        ]);

        $this->assertDatabaseCount('product_attribute_attribute_option', 2);
    }

    /**
     * @dataProvider authProvider
     */
    public function testCreateWithAttributeMultipleOptions(string $user): void
    {
        $this->{$user}->givePermissionTo('products.add');

        $attribute = Attribute::factory()->create([
            'type' => AttributeType::MULTI_CHOICE_OPTION,
        ]);

        $option = $attribute->options()->create([
            'index' => 1,
        ]);
        $option->setLocale($this->lang)->fill(['name' => 'first option']);
        $option->save();

        $option2 = $attribute->options()->create([
            'index' => 1,
        ]);
        $option2->setLocale($this->lang)->fill(['name' => 'second option']);
        $option2->save();

        $response = $this
            ->actingAs($this->{$user})
            ->postJson('/products', [
                'translations' => [
                    $this->lang => [
                        'name' => 'Test',
                    ],
                ],
                'published' => [$this->lang],
                'slug' => 'test',
                'prices_base' => $this->productPrices,
                'public' => true,
                'shipping_digital' => false,
                'attributes' => [
                    $attribute->getKey() => [
                        $option->getKey(),
                        $option2->getKey(),
                    ],
                ],
            ])
            ->assertCreated()
            ->assertJsonFragment([
                'id' => $attribute->getKey(),
                'name' => $attribute->name,
                'slug' => $attribute->slug,
                'description' => $attribute->description,
                'type' => $attribute->type,
                'global' => $attribute->global,
                'sortable' => $attribute->sortable,
            ])
            ->assertJsonFragment([
                'id' => $option->getKey(),
                'name' => $option->name,
                'index' => $option->index,
                'value_number' => $option->value_number,
                'value_date' => $option->value_date,
                'attribute_id' => $attribute->getKey(),
            ])
            ->assertJsonFragment([
                'id' => $option2->getKey(),
                'name' => $option2->name,
                'index' => $option2->index,
                'value_number' => $option2->value_number,
                'value_date' => $option2->value_date,
                'attribute_id' => $attribute->getKey(),
            ]);

        $this->assertDatabaseHas('products', [
            'slug' => 'test',
            "name->{$this->lang}" => 'Test',
        ]);

        /** @var Product $product */
        $product = Product::query()->find($response->json('data.id'));

        $productAttribute = ProductAttribute::where('product_id', $product->getKey())
            ->where('attribute_id', $attribute->getKey())
            ->first();

        $this->assertDatabaseHas('product_attribute', [
            'product_id' => $product->getKey(),
            'attribute_id' => $attribute->getKey(),
        ]);

        $this->assertDatabaseHas('product_attribute_attribute_option', [
            'product_attribute_id' => $productAttribute->getKey(),
            'attribute_option_id' => $option->getKey(),
        ]);

        $this->assertDatabaseHas('product_attribute_attribute_option', [
            'product_attribute_id' => $productAttribute->getKey(),
            'attribute_option_id' => $option2->getKey(),
        ]);

        $this->assertDatabaseCount('product_attribute_attribute_option', 2);
    }

    /**
     * @dataProvider authProvider
     */
    public function testCreateWithAttributeInvalidMultipleOptions(string $user): void
    {
        $this->{$user}->givePermissionTo('products.add');

        $attribute = Attribute::factory()->create([
            'type' => AttributeType::SINGLE_OPTION,
        ]);

        $option = $attribute->options()->create([
            'index' => 1,
        ]);

        $option2 = $attribute->options()->create([
            'index' => 1,
        ]);

        $this
            ->actingAs($this->{$user})
            ->postJson('/products', [
                'name' => 'Test',
                'slug' => 'test',
                'prices_base' => $this->productPrices,
                'public' => true,
                'shipping_digital' => false,
                'attributes' => [
                    $attribute->getKey() => [
                        $option->getKey(),
                        $option2->getKey(),
                    ],
                ],
            ])
            ->assertUnprocessable();
    }

    /**
     * @dataProvider authProvider
     */
    public function testCreateWithAttributeInvalidOption(string $user): void
    {
        $this->{$user}->givePermissionTo('products.add');

        $attribute = Attribute::factory()->create();

        $attribute2 = Attribute::factory()->create();

        $option = $attribute2->options()->create([
            'index' => 1,
        ]);

        $this
            ->actingAs($this->{$user})
            ->postJson('/products', [
                'translations' => [
                    $this->lang => [
                        'name' => 'Test',
                    ],
                ],
                'published' => [$this->lang],
                'slug' => 'test',
                'prices_base' => $this->productPrices,
                'public' => true,
                'shipping_digital' => false,
                'attributes' => [
                    $attribute->getKey() => [
                        $option->getKey(),
                    ],
                ],
            ])
            ->assertUnprocessable();
    }

    /**
     * @dataProvider authProvider
     */
    public function testCreateWithExistingSale(string $user): void
    {
        $this->{$user}->givePermissionTo('products.add');

        $saleNotApplied = Discount::factory()->create([
            'target_type' => DiscountTargetType::PRODUCTS,
            'target_is_allow_list' => true,
            'code' => null,
            'percentage' => null,
        ]);

        $this->discountRepository->setDiscountAmounts($saleNotApplied->getKey(), [
            PriceDto::from([
                'value' => '10.00',
                'currency' => $this->currency,
            ])
        ]);

        $saleApplied = Discount::factory()->create([
            'target_type' => DiscountTargetType::PRODUCTS,
            'target_is_allow_list' => false,
            'code' => null,
            'percentage' => null,
        ]);

        $this->discountRepository->setDiscountAmounts($saleApplied->getKey(), [
            PriceDto::from([
                'value' => '20.00',
                'currency' => $this->currency,
            ])
        ]);

        $response = $this->actingAs($this->{$user})->postJson('/products', [
            'translations' => [
                $this->lang => [
                    'name' => 'Test',
                ],
            ],
            'published' => [$this->lang],
            'slug' => 'test',
            'prices_base' => $this->productPrices,
            'public' => true,
            'shipping_digital' => false,
        ]);

        $productId = $response->json('data.id');
        $response
            ->assertCreated()
            ->assertJsonFragment([
                'id' => $saleApplied->getKey(),
            ])
            ->assertJsonMissing([
                'id' => $saleNotApplied->getKey(),
            ]);

        $this->assertDatabaseHas('prices', [
            'model_id' => $productId,
            'price_type' => ProductPriceType::PRICE_MIN,
            'value' => 80 * 100,
        ]);
        $this->assertDatabaseHas('prices', [
            'model_id' => $productId,
            'price_type' => ProductPriceType::PRICE_MAX,
            'value' => 80 * 100,
        ]);
    }

    /**
     * @dataProvider authProvider
     */
    public function testCreateIncompleteTranslations(string $user): void
    {
        $this->{$user}->givePermissionTo('products.add');

        /** @var Language $en */
        $en = Language::where('iso', '=', 'en')->first();

        $this->actingAs($this->{$user})->postJson('/products', [
            'slug' => 'test',
            'prices_base' => $this->productPrices,
            'public' => true,
            'shipping_digital' => false,
            'translations' => [
                $this->lang => [
                    'name' => 'Test',
                    'description_html' => '<h1>Description</h1>',
                    'description_short' => 'So called short description...',
                ],
            ],
            'published' => [$this->lang, $en->getKey()],
        ])
            ->assertUnprocessable()
            ->assertJsonFragment([
                'message' => "Model doesn't have all required translations to be published in {$en->getKey()}",
                'key' => Exceptions::PUBLISHING_TRANSLATION_EXCEPTION->name,
            ]);
    }
}
