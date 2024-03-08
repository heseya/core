<?php

namespace Tests\Feature\Products;

use App\Enums\DiscountTargetType;
use App\Events\ProductUpdated;
use App\Listeners\WebHookEventListener;
use App\Models\Discount;
use App\Models\Media;
use App\Models\Product;
use App\Models\WebHook;
use App\Repositories\DiscountRepository;
use App\Services\Contracts\DiscountServiceContract;
use App\Services\ProductService;
use App\Services\SchemaCrudService;
use Brick\Math\Exception\NumberFormatException;
use Brick\Math\Exception\RoundingNecessaryException;
use Brick\Money\Exception\UnknownCurrencyException;
use Domain\Currency\Currency;
use Domain\Page\Page;
use Domain\Price\Dtos\PriceDto;
use Domain\Price\Enums\ProductPriceType;
use Domain\Product\Models\ProductBannerMedia;
use Domain\ProductSet\ProductSet;
use Domain\Seo\Models\SeoMetadata;
use Heseya\Dto\DtoException;
use Illuminate\Events\CallQueuedListener;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;
use Spatie\WebhookServer\CallWebhookJob;
use Tests\Utils\FakeDto;

class ProductUpdateTest extends ProductTestCase
{
    private SchemaCrudService $schemaCrudService;
    private ProductService $productService;
    private DiscountRepository $discountRepository;
    private DiscountServiceContract $discountService;

    public function setUp(): void
    {
        parent::setUp();

        $this->productService = App::make(ProductService::class);
        $this->schemaCrudService = App::make(SchemaCrudService::class);
        $this->discountRepository = App::make(DiscountRepository::class);
        $this->discountService = App::make(DiscountServiceContract::class);
    }

    public function testUpdateUnauthorized(): void
    {
        Event::fake([ProductUpdated::class]);
        $this->patchJson('/products/id:' . $this->product->getKey())
            ->assertForbidden();
        Event::assertNotDispatched(ProductUpdated::class);
    }

    /**
     * @dataProvider authProvider
     */
    public function testUpdate(string $user): void
    {
        $this->{$user}->givePermissionTo('products.edit');

        $this->actingAs($this->{$user})->patchJson('/products/id:' . $this->product->getKey(), [
            'translations' => [
                $this->lang => [
                    'name' => 'Updated',
                    'description_html' => '<h1>New description</h1>',
                    'description_short' => 'New so called short description',
                ],
            ],
            'published' => [$this->lang],
            'slug' => 'updated',
            'public' => false,
        ])->assertOk();

        $this->assertDatabaseHas('products', [
            'id' => $this->product->getKey(),
            "name->{$this->lang}" => 'Updated',
            'slug' => 'updated',
            "description_html->{$this->lang}" => '<h1>New description</h1>',
            "description_short->{$this->lang}" => 'New so called short description',
            'public' => false,
        ]);
    }

    /**
     * @dataProvider authProvider
     */
    public function testUpdateNoPublished(string $user): void
    {
        $this->{$user}->givePermissionTo('products.edit');

        $this->actingAs($this->{$user})->patchJson('/products/id:' . $this->product->getKey(), [
            'translations' => [
                $this->lang => [
                    'name' => 'Updated',
                    'description_html' => '<h1>New description</h1>',
                    'description_short' => 'New so called short description',
                ],
            ],
            'slug' => 'updated',
            'public' => false,
        ])
            ->assertOk()
            ->assertJsonFragment([
                'published' => [
                    $this->lang,
                ],
            ]);

        $this->assertDatabaseHas('products', [
            'id' => $this->product->getKey(),
            "name->{$this->lang}" => 'Updated',
            'slug' => 'updated',
            "description_html->{$this->lang}" => '<h1>New description</h1>',
            "description_short->{$this->lang}" => 'New so called short description',
            'public' => false,
            'published' => json_encode([$this->lang]),
        ]);
    }

    /**
     * @dataProvider authProvider
     */
    public function testUpdateDigital(string $user): void
    {
        $this->{$user}->givePermissionTo('products.edit');

        $this
            ->actingAs($this->{$user})
            ->patchJson('/products/id:' . $this->product->getKey(), [
                'shipping_digital' => true,
            ])
            ->assertOk()
            ->assertJson([
                'data' => [
                    'shipping_digital' => true,
                ],
            ]);

        $this->assertDatabaseHas('products', [
            $this->product->getKeyName() => $this->product->getKey(),
            'shipping_digital' => true,
        ]);
    }

    /**
     * @dataProvider authProvider
     */
    public function testUpdateWithWebHookQueue(string $user): void
    {
        $this->{$user}->givePermissionTo('products.edit');

        $webHook = WebHook::factory()->create([
            'events' => [
                'ProductUpdated',
            ],
            'model_type' => $this->{$user}::class,
            'creator_id' => $this->{$user}->getKey(),
            'with_issuer' => false,
            'with_hidden' => true,
        ]);

        Queue::fake();

        $response = $this->actingAs($this->{$user})->patchJson('/products/id:' . $this->product->getKey(), [
            'slug' => 'updated',
        ]);
        $response->assertOk();

        Queue::assertPushed(CallQueuedListener::class, function ($job) {
            return $job->class === WebHookEventListener::class
                && $job->data[0] instanceof ProductUpdated;
        });

        $product = Product::find($this->product->getKey());
        $event = new ProductUpdated($product);
        $listener = new WebHookEventListener();

        $listener->handle($event);

        Queue::assertPushed(CallWebhookJob::class, function ($job) use ($webHook, $product) {
            $payload = $job->payload;

            return $job->webhookUrl === $webHook->url
                && isset($job->headers['Signature'])
                && $payload['data']['id'] === $product->getKey()
                && $payload['data_type'] === 'Product'
                && $payload['event'] === 'ProductUpdated';
        });
    }

    /**
     * @dataProvider authProvider
     */
    public function testUpdateWithWebHookDispatched(string $user): void
    {
        $this->{$user}->givePermissionTo('products.edit');

        $webHook = WebHook::factory()->create([
            'events' => [
                'ProductUpdated',
            ],
            'model_type' => $this->{$user}::class,
            'creator_id' => $this->{$user}->getKey(),
            'with_issuer' => false,
            'with_hidden' => true,
        ]);

        Bus::fake();

        $this->actingAs($this->{$user})->patchJson('/products/id:' . $this->product->getKey(), [
            'slug' => 'updated',
        ]);

        Bus::assertDispatched(CallQueuedListener::class, function ($job) {
            return $job->class === WebHookEventListener::class
                && $job->data[0] instanceof ProductUpdated;
        });

        $product = Product::find($this->product->getKey());
        $event = new ProductUpdated($product);
        $listener = new WebHookEventListener();

        $listener->handle($event);

        Bus::assertDispatched(CallWebhookJob::class, function ($job) use ($webHook, $product) {
            $payload = $job->payload;

            return $job->webhookUrl === $webHook->url
                && isset($job->headers['Signature'])
                && $payload['data']['id'] === $product->getKey()
                && $payload['data_type'] === 'Product'
                && $payload['event'] === 'ProductUpdated';
        });
    }

    /**
     * @dataProvider authProvider
     */
    public function testUpdateChangeSets(string $user): void
    {
        $this->{$user}->givePermissionTo('products.edit');

        Event::fake([ProductUpdated::class]);

        $set1 = ProductSet::factory()->create();
        $set2 = ProductSet::factory()->create();
        $set3 = ProductSet::factory()->create();

        $this->product->sets()->sync([$set1->getKey(), $set2->getKey()]);

        $response = $this->actingAs($this->{$user})->patchJson('/products/id:' . $this->product->getKey(), [
            'name' => $this->product->name,
            'slug' => $this->product->slug,
            'public' => $this->product->public,
            'sets' => [
                $set2->getKey(),
                $set3->getKey(),
            ],
        ]);

        $this->assertDatabaseHas('product_set_product', [
            'product_id' => $this->product->getKey(),
            'product_set_id' => $set2->getKey(),
        ]);

        $this->assertDatabaseHas('product_set_product', [
            'product_id' => $this->product->getKey(),
            'product_set_id' => $set3->getKey(),
        ]);

        $this->assertDatabaseMissing('product_set_product', [
            'product_id' => $this->product->getKey(),
            'product_set_id' => $set1->getKey(),
        ]);

        Event::assertDispatched(ProductUpdated::class);
    }

    /**
     * @dataProvider authProvider
     */
    public function testUpdateSetsCheckOrder(string $user): void
    {
        $this->{$user}->givePermissionTo('products.edit');

        Event::fake([ProductUpdated::class]);

        $set1 = ProductSet::factory()->create();
        $set2 = ProductSet::factory()->create();
        $set3 = ProductSet::factory()->create();

        $product1 = Product::factory()->create();
        $product2 = Product::factory()->create();
        $set3->products()->attach([
            $product1->getKey(),
            $product2->getKey(),
        ]);
        $set3->descendantProducts()->attach([
            $product1->getKey() => ['order' => 0],
            $product2->getKey() => ['order' => 1],
        ]);

        $this->product->sets()->attach([$set1->getKey(), $set2->getKey()]);
        $this->product->ancestorSets()->attach([
            $set1->getKey() => ['order' => 0],
            $set2->getKey() => ['order' => 0],
        ]);

        $this->actingAs($this->{$user})->patchJson('/products/id:' . $this->product->getKey(), [
            'name' => $this->product->name,
            'slug' => $this->product->slug,
            'public' => $this->product->public,
            'sets' => [
                $set2->getKey(),
                $set3->getKey(),
            ],
        ]);

        $this->assertDatabaseHas('product_set_product_descendant', [
            'product_id' => $this->product->getKey(),
            'product_set_id' => $set2->getKey(),
            'order' => 0,
        ]);

        $this->assertDatabaseHas('product_set_product_descendant', [
            'product_id' => $this->product->getKey(),
            'product_set_id' => $set3->getKey(),
            'order' => 2,
        ]);

        $this->assertDatabaseMissing('product_set_product_descendant', [
            'product_id' => $this->product->getKey(),
            'product_set_id' => $set1->getKey(),
            'order' => 0,
        ]);

        Event::assertDispatched(ProductUpdated::class);
    }

    /**
     * @dataProvider authProvider
     */
    public function testUpdateDeleteSets(string $user): void
    {
        $this->{$user}->givePermissionTo('products.edit');

        Event::fake([ProductUpdated::class]);

        $set1 = ProductSet::factory()->create();
        $set2 = ProductSet::factory()->create();

        $this->product->sets()->sync([$set1->getKey(), $set2->getKey()]);

        $this->actingAs($this->{$user})->patchJson('/products/id:' . $this->product->getKey(), [
            'sets' => [],
        ]);

        $this->assertDatabaseMissing('product_set_product', [
            'product_id' => $this->product->getKey(),
        ]);

        Event::assertDispatched(ProductUpdated::class);
    }

    /**
     * @dataProvider authProvider
     */
    public function testUpdateWithSeoOk(string $user): void
    {
        $this->{$user}->givePermissionTo('products.edit');

        $this->product->update([
            'name' => 'Created',
            'slug' => 'created',
            'description_html' => '<h1>Description</h1>',
            'public' => false,
        ]);

        $this->product->seo()->save(SeoMetadata::factory()->make());

        $this
            ->actingAs($this->{$user})
            ->json('PATCH', '/products/id:' . $this->product->getKey(), [
                'seo' => [
                    'translations' => [
                        $this->lang => [
                            'title' => 'seo title',
                            'description' => 'seo description',
                            'no_index' => false,
                        ],
                    ],
                ],
            ])
            ->assertJsonFragment([
                'title' => 'seo title',
                'description' => 'seo description',
                'no_index' => false,
            ]);

        $this->assertDatabaseHas('seo_metadata', [
            "title->{$this->lang}" => 'seo title',
            "description->{$this->lang}" => 'seo description',
        ]);
    }

    /**
     * @dataProvider authProvider
     */
    public function testUpdateSeo(string $user): void
    {
        $this->{$user}->givePermissionTo('products.edit');

        $this->product->update([
            'name' => 'Created',
            'slug' => 'created',
            'description_html' => '<h1>Description</h1>',
            'public' => false,
        ]);

        $this
            ->actingAs($this->{$user})
            ->json('PATCH', '/products/id:' . $this->product->getKey(), [
                'seo' => [
                    'translations' => [
                        $this->lang => [
                            'title' => 'seo title',
                            'description' => 'seo description',
                        ],
                    ],
                ],
            ])
            ->assertJsonFragment([
                'title' => 'seo title',
                'description' => 'seo description',
            ]);

        $this->assertDatabaseHas('seo_metadata', [
            "title->{$this->lang}" => 'seo title',
            "description->{$this->lang}" => 'seo description',
        ]);
    }

    /**
     * @dataProvider authProvider
     */
    public function testUpdateSeoEmptyValues(string $user): void
    {
        $this->{$user}->givePermissionTo('products.edit');

        $this->product->update([
            'name' => 'Created',
            'slug' => 'created',
            'description_html' => '<h1>Description</h1>',
            'public' => false,
        ]);

        $this
            ->actingAs($this->{$user})
            ->json('PATCH', '/products/id:' . $this->product->getKey(), [
                'seo' => [
                    'translations' => [
                        $this->lang => [
                            'title' => '',
                            'description' => '',
                        ],
                    ],
                ],
            ])
            ->assertJsonFragment([
                'title' => '',
                'description' => '',
            ]);

        $this->assertDatabaseHas('seo_metadata', [
            "title->{$this->lang}" => null,
            "description->{$this->lang}" => null,
        ]);
    }

    /**
     * @dataProvider authProvider
     */
    public function testUpdateSeoPublished(string $user): void
    {
        $this->{$user}->givePermissionTo('products.edit');

        $this->product->update([
            'name' => 'Created',
            'slug' => 'created',
            'description_html' => '<h1>Description</h1>',
            'public' => false,
        ]);

        $this
            ->actingAs($this->{$user})
            ->json('PATCH', '/products/id:' . $this->product->getKey(), [
                'seo' => [
                    'translations' => [
                        $this->lang => [
                            'title' => 'seo title',
                            'description' => 'seo description',
                        ],
                    ],
                    'published' => [
                        $this->lang,
                    ],
                ],
            ])
            ->assertJsonFragment([
                'title' => 'seo title',
                'description' => 'seo description',
                'published' => [
                    $this->lang,
                ],
            ]);

        $this->assertDatabaseHas('seo_metadata', [
            "title->{$this->lang}" => 'seo title',
            "description->{$this->lang}" => 'seo description',
        ]);
    }

    /**
     * @dataProvider authProvider
     */
    public function testUpdateEmptySeo(string $user): void
    {
        $this->{$user}->givePermissionTo('products.edit');

        $this->product->update([
            'name' => 'Created',
            'slug' => 'created',
            'description_html' => '<h1>Description</h1>',
            'public' => false,
        ]);

        $this
            ->actingAs($this->{$user})
            ->json('PATCH', '/products/id:' . $this->product->getKey(), [
                'seo' => [],
            ])
            ->assertOk();
    }

    /**
     * @dataProvider authProvider
     */
    public function testUpdateMinMaxPrice(string $user): void
    {
        $this->{$user}->givePermissionTo('products.edit');

        $this->product->schemas()->detach();

        $schemaPrice = 50;
        $schema = $this->schemaCrudService->store(
            FakeDto::schemaDto([
                'type' => 0,
                'required' => false,
                'prices' => [['value' => $schemaPrice, 'currency' => Currency::DEFAULT->value]],
            ])
        );

        $this->product->schemas()->attach($schema->getKey());
        $this->productService->updateMinMaxPrices($this->product);

        $productNewPrice = 250;
        $prices = array_map(fn (Currency $currency) => [
            'value' => "{$productNewPrice}.00",
            'currency' => $currency->value,
        ], Currency::cases());

        $this->actingAs($this->{$user})->patchJson('/products/id:' . $this->product->getKey(), [
            'name' => $this->product->name,
            'slug' => $this->product->slug,
            'public' => $this->product->public,
            'prices_base' => $prices,
            'sets' => [],
            'schemas' => [
                $schema->getKey(),
            ],
        ]);

        $this->assertDatabaseHas('prices', [
            'model_id' => $this->product->getKey(),
            'price_type' => ProductPriceType::PRICE_BASE,
            'value' => $productNewPrice * 100,
        ]);
        $this->assertDatabaseHas('prices', [
            'model_id' => $this->product->getKey(),
            'price_type' => ProductPriceType::PRICE_MIN,
            'value' => $productNewPrice * 100,
        ]);
        $this->assertDatabaseHas('prices', [
            'model_id' => $this->product->getKey(),
            'price_type' => ProductPriceType::PRICE_MAX,
            'value' => ($productNewPrice + $schemaPrice) * 100,
        ]);
    }

    /**
     * @dataProvider authProvider
     */
    public function testUpdateMinMaxPriceWithSale(string $user): void
    {
        $this->{$user}->givePermissionTo('products.edit');

        $schemaPrice = 50;
        $schema = $this->schemaCrudService->store(
            FakeDto::schemaDto([
                'type' => 0,
                'required' => false,
                'prices' => [['value' => $schemaPrice, 'currency' => Currency::DEFAULT->value]],
            ])
        );

        $this->product->schemas()->attach($schema->getKey());
        $this->productService->updateMinMaxPrices($this->product);

        $saleValue = 25;
        $sale = Discount::factory()->create([
            'code' => null,
            'target_type' => DiscountTargetType::PRODUCTS,
            'target_is_allow_list' => true,
            'percentage' => null,
        ]);

        $this->discountRepository->setDiscountAmounts($sale->getKey(), [
            PriceDto::from([
                'value' => "{$saleValue}.00",
                'currency' => $this->currency,
            ])
        ]);

        $sale->products()->attach($this->product->getKey());

        $this->discountService->applyDiscountsOnProduct($this->product);

        $productNewPrice = 250;
        $prices = array_map(fn (Currency $currency) => [
            'value' => "{$productNewPrice}.00",
            'currency' => $currency->value,
        ], Currency::cases());

        $response = $this->actingAs($this->{$user})->patchJson('/products/id:' . $this->product->getKey(), [
            'name' => $this->product->name,
            'slug' => $this->product->slug,
            'public' => $this->product->public,
            'prices_base' => $prices,
            'sets' => [],
            'schemas' => [
                $schema->getKey(),
            ],
        ]);

        $response->assertOk();

        $this->assertDatabaseHas('prices', [
            'model_id' => $this->product->getKey(),
            'price_type' => ProductPriceType::PRICE_BASE->value,
            'value' => $productNewPrice * 100,
        ]);
        $this->assertDatabaseHas('prices', [
            'model_id' => $this->product->getKey(),
            'price_type' => ProductPriceType::PRICE_MIN_INITIAL->value,
            'value' => $productNewPrice * 100,
        ]);
        $this->assertDatabaseHas('prices', [
            'model_id' => $this->product->getKey(),
            'price_type' => ProductPriceType::PRICE_MAX_INITIAL->value,
            'value' => ($productNewPrice + $schemaPrice) * 100,
        ]);
        $this->assertDatabaseHas('prices', [
            'model_id' => $this->product->getKey(),
            'price_type' => ProductPriceType::PRICE_MIN->value,
            'value' => ($productNewPrice - $saleValue) * 100,
        ]);
        $this->assertDatabaseHas('prices', [
            'model_id' => $this->product->getKey(),
            'price_type' => ProductPriceType::PRICE_MAX->value,
            'value' => ($productNewPrice + $schemaPrice - $saleValue) * 100,
        ]);
    }

    /**
     * @dataProvider authProvider
     */
    public function testUpdateSchemaMinMaxPrice(string $user): void
    {
        $this->{$user}->givePermissionTo('products.edit');

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

        $schemaNewPrice = 75;
        $response = $this->actingAs($this->{$user})->patchJson(
            '/schemas/id:' . $schema->getKey(),
            FakeDto::schemaData([
                'name' => 'Test Updated',
                'prices' => [['value' => $schemaNewPrice, 'currency' => Currency::DEFAULT->value]],
                'type' => 'string',
                'required' => false,
            ])
        );

        $response->assertValid()->assertOk();

        $this->assertDatabaseHas('prices', [
            'model_id' => $this->product->getKey(),
            'price_type' => ProductPriceType::PRICE_BASE,
            'value' => 100 * 100,
            'currency' => $this->currency->value,
        ]);
        $this->assertDatabaseHas('prices', [
            'model_id' => $this->product->getKey(),
            'price_type' => ProductPriceType::PRICE_MIN,
            'value' => 100 * 100,
            'currency' => $this->currency->value,
        ]);
        $this->assertDatabaseHas('prices', [
            'model_id' => $this->product->getKey(),
            'price_type' => ProductPriceType::PRICE_MAX,
            'value' => (100 + $schemaNewPrice) * 100,
            'currency' => $this->currency->value,
        ]);
    }

    /**
     * @dataProvider authProvider
     */
    public function testUpdateSets(string $user): void
    {
        $this->{$user}->givePermissionTo('products.edit');

        Event::fake([ProductUpdated::class]);

        $parent = ProductSet::factory()->create([
            'public' => true,
        ]);

        $child = ProductSet::factory()->create([
            'public' => true,
            'parent_id' => $parent->getKey(),
        ]);

        $this->actingAs($this->{$user})->patchJson('/products/id:' . $this->product->getKey(), [
            'name' => $this->product->name,
            'slug' => $this->product->slug,
            'public' => $this->product->public,
            'sets' => [
                $child->getKey(),
            ],
        ])->assertOk();

        $this->assertDatabaseHas('product_set_product_descendant', [
            'product_id' => $this->product->getKey(),
            'product_set_id' => $parent->getKey(),
        ]);

        $this->assertDatabaseHas('product_set_product_descendant', [
            'product_id' => $this->product->getKey(),
            'product_set_id' => $child->getKey(),
        ]);

        $this->assertDatabaseHas('product_set_product', [
            'product_id' => $this->product->getKey(),
            'product_set_id' => $child->getKey(),
        ]);

        $this->assertDatabaseMissing('product_set_product', [
            'product_id' => $this->product->getKey(),
            'product_set_id' => $parent->getKey(),
        ]);

        Event::assertDispatched(ProductUpdated::class);
    }

    /**
     * @dataProvider authProvider
     */
    public function testUpdateNewSets(string $user): void
    {
        $this->{$user}->givePermissionTo('products.edit');

        Event::fake([ProductUpdated::class]);

        $parent = ProductSet::factory()->create([
            'public' => true,
        ]);

        $child = ProductSet::factory()->create([
            'public' => true,
            'parent_id' => $parent->getKey(),
        ]);

        $newParent = ProductSet::factory()->create([
            'public' => true,
        ]);

        $newChild = ProductSet::factory()->create([
            'public' => true,
            'parent_id' => $newParent->getKey(),
        ]);

        $this->product->sets()->attach([$child->getKey()]);
        $this->product->ancestorSets()->attach([
            $parent->getKey() => ['order' => 0],
            $child->getKey() => ['order' => 0],
        ]);

        $this->actingAs($this->{$user})->patchJson('/products/id:' . $this->product->getKey(), [
            'name' => $this->product->name,
            'slug' => $this->product->slug,
            'public' => $this->product->public,
            'sets' => [
                $newChild->getKey(),
            ],
        ])->assertOk();

        $this->assertDatabaseHas('product_set_product_descendant', [
            'product_id' => $this->product->getKey(),
            'product_set_id' => $newParent->getKey(),
        ]);

        $this->assertDatabaseHas('product_set_product_descendant', [
            'product_id' => $this->product->getKey(),
            'product_set_id' => $newChild->getKey(),
        ]);

        $this->assertDatabaseMissing('product_set_product_descendant', [
            'product_id' => $this->product->getKey(),
            'product_set_id' => $parent->getKey(),
        ]);

        $this->assertDatabaseMissing('product_set_product_descendant', [
            'product_id' => $this->product->getKey(),
            'product_set_id' => $child->getKey(),
        ]);

        $this->assertDatabaseHas('product_set_product', [
            'product_id' => $this->product->getKey(),
            'product_set_id' => $newChild->getKey(),
        ]);

        $this->assertDatabaseMissing('product_set_product', [
            'product_id' => $this->product->getKey(),
            'product_set_id' => $child->getKey(),
        ]);

        $this->assertDatabaseMissing('product_set_product', [
            'product_id' => $this->product->getKey(),
            'product_set_id' => $newParent->getKey(),
        ]);

        Event::assertDispatched(ProductUpdated::class);
    }

    /**
     * @dataProvider authProvider
     *
     * @throws NumberFormatException
     * @throws RoundingNecessaryException
     * @throws UnknownCurrencyException
     * @throws DtoException
     */
    public function testUpdateDescriptions(string $user): void
    {
        /** @var ProductService $productService */
        $productService = App::make(ProductService::class);
        $product = $productService->create(FakeDto::productCreateDto());
        $page = Page::factory()->create();

        $this->{$user}->givePermissionTo('products.edit');
        $response = $this
            ->actingAs($this->{$user})
            ->json('PATCH', "/products/id:{$product->getKey()}", [
                'descriptions' => [$page->getKey()],
            ]);

        $response->assertOk()
            ->assertJsonPath('data.descriptions.0.id', $page->getKey());

        $this->assertDatabaseHas('product_page', [
            'product_id' => $response->json('data.id'),
            'page_id' => $page->getKey(),
        ]);
    }

    /**
     * @dataProvider authProvider
     */
    public function testUpdateProductBannerNew(string $user): void
    {
        $this->{$user}->givePermissionTo('products.edit');

        /** @var ProductService $productService */
        $productService = App::make(ProductService::class);
        $product = $productService->create(FakeDto::productCreateDto());

        $media = Media::factory()->create();

        $this
            ->actingAs($this->{$user})
            ->json('PATCH', "/products/id:{$product->getKey()}", [
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
            ->assertOk()
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
    public function testUpdateProductBannerNewNoTitle(string $user): void
    {
        $this->{$user}->givePermissionTo('products.edit');

        /** @var ProductService $productService */
        $productService = App::make(ProductService::class);
        $product = $productService->create(FakeDto::productCreateDto());

        $media = Media::factory()->create();

        $this
            ->actingAs($this->{$user})
            ->json('PATCH', "/products/id:{$product->getKey()}", [
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
            ->assertOk()
            ->assertJsonFragment([
                'min_screen_width' => 1024,
            ]);

        $this->assertDatabaseCount('product_banner_responsive_media', 1);
    }

    /**
     * @dataProvider authProvider
     */
    public function testUpdateProductBannerExisting(string $user): void
    {
        $this->{$user}->givePermissionTo('products.edit');

        /** @var ProductBannerMedia $bannerMedia */
        $bannerMedia = ProductBannerMedia::factory()->create();

        $media = Media::factory()->create();
        $bannerMedia->media()->attach([$media->getKey() => ['min_screen_width' => 512]]);

        /** @var ProductService $productService */
        $productService = App::make(ProductService::class);
        $product = $productService->create(FakeDto::productCreateDto());
        $bannerMedia->product()->save($product);

        $newMedia = Media::factory()->create();

        $this
            ->actingAs($this->{$user})
            ->json('PATCH', "/products/id:{$product->getKey()}", [
                'banner' => [
                    'url' => 'http://new.example.com',
                    'translations' => [
                        $this->lang => [
                            'title' => 'new title',
                            'subtitle' => 'new subtitle',
                        ],
                    ],
                    'media' => [
                        [
                            'min_screen_width' => 1024,
                            'media' => $newMedia->getKey(),
                        ],
                    ],
                ],
            ])
            ->assertOk()
            ->assertJsonFragment([
                'url' => 'http://new.example.com',
                'title' => 'new title',
                'subtitle' => 'new subtitle',
            ])
            ->assertJsonFragment([
                'min_screen_width' => 1024,
            ]);

        $this->assertDatabaseHas('product_banner_media', [
            'id' => $bannerMedia->getKey(),
            "title->{$this->lang}" => 'new title',
            "subtitle->{$this->lang}" => 'new subtitle',
        ]);

        $this->assertDatabaseHas('product_banner_responsive_media', [
            'product_banner_media_id' => $bannerMedia->getKey(),
            'media_id' => $newMedia->getKey(),
        ]);

        $this->assertDatabaseMissing('product_banner_responsive_media', [
            'product_banner_media_id' => $bannerMedia->getKey(),
            'media_id' => $media->getKey(),
        ]);
    }

    /**
     * @dataProvider authProvider
     */
    public function testUpdateProductBannerExistingNoTitle(string $user): void
    {
        $this->{$user}->givePermissionTo('products.edit');

        /** @var ProductBannerMedia $bannerMedia */
        $bannerMedia = ProductBannerMedia::factory()->create();

        $media = Media::factory()->create();
        $bannerMedia->media()->attach([$media->getKey() => ['min_screen_width' => 512]]);

        /** @var ProductService $productService */
        $productService = App::make(ProductService::class);
        $product = $productService->create(FakeDto::productCreateDto());
        $bannerMedia->product()->save($product);

        $newMedia = Media::factory()->create();

        $this
            ->actingAs($this->{$user})
            ->json('PATCH', "/products/id:{$product->getKey()}", [
                'banner' => [
                    'title' => $bannerMedia->title,
                    'subtitle' => $bannerMedia->subtitle,
                    'url' => 'http://new.example.com',
                    'translations' => [
                        $this->lang => [
                            'title' => '',
                            'subtitle' => '',
                        ],
                    ],
                    'media' => [
                        [
                            'min_screen_width' => 1024,
                            'media' => $newMedia->getKey(),
                        ],
                    ],
                ],
            ])
            ->assertOk()
            ->assertJsonFragment([
                'min_screen_width' => 1024,
            ]);

        $this->assertDatabaseHas('product_banner_responsive_media', [
            'product_banner_media_id' => $bannerMedia->getKey(),
            'media_id' => $newMedia->getKey(),
        ]);

        $this->assertDatabaseMissing('product_banner_responsive_media', [
            'product_banner_media_id' => $bannerMedia->getKey(),
            'media_id' => $media->getKey(),
        ]);
    }

    /**
     * @dataProvider authProvider
     */
    public function testUpdateProductBannerRemove(string $user): void
    {
        $this->{$user}->givePermissionTo('products.edit');

        /** @var ProductBannerMedia $bannerMedia */
        $bannerMedia = ProductBannerMedia::factory()->create();

        $media = Media::factory()->create();
        $bannerMedia->media()->attach([$media->getKey() => ['min_screen_width' => 512]]);

        /** @var ProductService $productService */
        $productService = App::make(ProductService::class);
        $product = $productService->create(FakeDto::productCreateDto());
        $bannerMedia->product()->save($product);

        $this
            ->actingAs($this->{$user})
            ->json('PATCH', "/products/id:{$product->getKey()}", [
                'banner' => null,
            ])
            ->assertOk()
            ->assertJsonMissing([
                'url' => 'http://new.example.com',
                'title' => 'new title',
                'subtitle' => 'new subtitle',
            ])
            ->assertJsonMissing([
                'min_screen_width' => 1024,
            ]);

        $this->assertDatabaseMissing('product_banner_media', [
            'id' => $bannerMedia->getKey(),
            "title->{$this->lang}" => 'new title',
            "subtitle->{$this->lang}" => 'new subtitle',
        ]);

        $this->assertDatabaseMissing('product_banner_responsive_media', [
            'product_banner_media_id' => $bannerMedia->getKey(),
            'media_id' => $media->getKey(),
        ]);
    }
}
