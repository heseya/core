<?php

namespace Tests\Feature;

use App\Enums\MediaType;
use App\Enums\SchemaType;
use App\Enums\MetadataType;
use App\Events\ProductCreated;
use App\Events\ProductDeleted;
use App\Events\ProductUpdated;
use App\Listeners\WebHookEventListener;
use App\Models\Attribute;
use App\Models\AttributeOption;
use App\Models\Media;
use App\Models\Product;
use App\Models\ProductAttribute;
use App\Models\ProductSet;
use App\Models\Schema;
use App\Models\SeoMetadata;
use App\Models\WebHook;
use App\Services\Contracts\AvailabilityServiceContract;
use App\Services\Contracts\ProductServiceContract;
use Carbon\Carbon;
use Illuminate\Events\CallQueuedListener;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;
use Spatie\WebhookServer\CallWebhookJob;
use Tests\Support\ElasticTest;
use Tests\TestCase;

class ProductTest extends TestCase
{
    use ElasticTest;

    private Product $product;
    private Product $hidden_product;

    private array $expected;
    private array $expected_short;
    private array $expected_attribute;
    private array $expected_attribute_short;

    private ProductServiceContract $productService;
    private AvailabilityServiceContract $availabilityService;

    public function setUp(): void
    {
        parent::setUp();

        $this->productService = App::make(ProductServiceContract::class);
        $this->availabilityService = App::make(AvailabilityServiceContract::class);

        $this->product = Product::factory()->create([
            'public' => true,
            'order' => 1,
        ]);

        $schema = $this->product->schemas()->create([
            'name' => 'Rozmiar',
            'type' => SchemaType::SELECT,
            'price' => 0,
            'required' => true,
        ]);

        $this->travel(5)->hours();

        $l = $schema->options()->create([
            'name' => 'L',
            'price' => 0,
        ]);

        $l->items()->create([
            'name' => 'Koszulka L',
            'sku' => 'K001/L',
        ]);

        $this->travelBack();

        $xl = $schema->options()->create([
            'name' => 'XL',
            'price' => 0,
        ]);

        $item = $xl->items()->create([
            'name' => 'Koszulka XL',
            'sku' => 'K001/XL',
        ]);

        $item->deposits()->create([
            'quantity' => 10,
        ]);

        $metadata = $this->product->metadata()->create([
            'name' => 'testMetadata',
            'value' => 'value metadata',
            'value_type' => MetadataType::STRING,
            'public' => true
        ]);

        $this->hidden_product = Product::factory()->create([
            'public' => false,
        ]);

        $attribute = Attribute::factory()->create();

        $option = AttributeOption::factory()->create([
            'index' => 1,
            'attribute_id' => $attribute->getKey()
        ]);

        $this->product->attributes()->attach($attribute->getKey());

        $this->product->attributes->first()->pivot->options()->attach($option->getKey());

        $this->availabilityService->calculateAvailabilityOnOrderAndRestock($item);

        /**
         * Expected short response
         */
        $this->expected_short = [
            'id' => $this->product->getKey(),
            'name' => $this->product->name,
            'slug' => $this->product->slug,
            'price' => (int) $this->product->price,
            'visible' => $this->product->public,
            'public' => (bool) $this->product->public,
            'available' => true,
            'cover' => null,
        ];

        $this->expected_attribute_short = [
            'attributes' => [
                [
                    'name' => $attribute->name,
                    'selected_options' => [
                        [
                            'id' => $option->getKey(),
                            'name' => $option->name,
                            'index' => $option->index,
                            'value_number' => $option->value_number,
                            'value_date' => $option->value_date,
                            'attribute_id' => $attribute->getKey(),
                        ],
                    ],
                ]
            ],
        ];

        $this->expected_attribute = $this->expected_attribute_short;
        $this->expected_attribute['attributes'][0] += [
            'id' => $attribute->getKey(),
            'slug' => $attribute->slug,
            'description' => $attribute->description,
            'type' => $attribute->type,
            'global' => $attribute->global,
            'sortable' => $attribute->sortable,
        ];

        /**
         * Expected full response
         */
        $this->expected = array_merge($this->expected_short, $this->expected_attribute, [
            'description_html' => $this->product->description_html,
            'description_short' => $this->product->description_short,
            'meta_description' => strip_tags($this->product->description_html),
            'gallery' => [],
            'schemas' => [[
                'name' => 'Rozmiar',
                'type' => 'select',
                'required' => true,
                'available' => true,
                'price' => 0,
                'metadata' => [],
                'options' => [
                    [
                        'name' => 'XL',
                        'price' => 0,
                        'disabled' => false,
                        'available' => true,
                        'items' => [[
                            'name' => 'Koszulka XL',
                            'sku' => 'K001/XL',
                        ],
                        ],
                        'metadata' => [],
                    ],
                    [
                        'name' => 'L',
                        'price' => 0,
                        'disabled' => false,
                        'available' => false,
                        'items' => [[
                            'name' => 'Koszulka L',
                            'sku' => 'K001/L',
                        ],
                        ],
                        'metadata' => [],
                    ],
                ],
            ],
            ],
            'metadata' => [
                $metadata->name => $metadata->value
            ],
        ]);
    }

    public function testIndexUnauthorized(): void
    {
        $this->getJson('/products')->assertForbidden();
    }

    /**
     * @dataProvider authProvider
     */
    public function testIndex($user): void
    {
        $this->$user->givePermissionTo('products.show');

        $product = Product::factory()->create([
            'public' => true,
        ]);
        $set = ProductSet::factory()->create([
            'public' => true,
            'hide_on_index' => true,
        ]);
        $product->sets()->sync([$set->getKey()]);

        $this
            ->actingAs($this->$user)
            ->json('GET', '/products', ['limit' => 100])
            ->assertOk();
//            ->assertJsonCount(1, 'data') // Should show only public products.
//            ->assertJson(['data' => [
//                0 => $this->expected_short,
//            ]]);

        $this->assertElasticQuery([
            'bool' => [
                'must' =>  [],
                'should' => [],
                'filter' =>  [
                    [
                        'term' => [
                            'public' => [
                                'value' => true,
                                'boost' => 1.0,
                            ],
                        ],
                    ],
                    [
                        'term' => [
                            'hide_on_index' => [
                                'value' => false,
                                'boost' => 1.0,
                            ],
                        ],
                    ],
                ],
            ],
        ], 100);

        $this->assertQueryCountLessThan(20);
    }

    /**
     * @dataProvider authProvider
     */
    public function testIndexIdsSearch($user): void
    {
        $this->$user->givePermissionTo('products.show');

        $firstProduct = Product::factory()->create([
            'public' => true,
        ]);

        $secondProduct = Product::factory()->create([
            'public' => true,
            'created_at' => Carbon::now()->addHour(),
        ]);

        // Dummy product to check if response will return only 2 products created above
        Product::factory()->create([
            'public' => true,
            'created_at' => Carbon::now()->addHour(),
        ]);

        $this
            ->actingAs($this->$user)
            ->json('GET', '/products', [
                'ids' => "{$firstProduct->getKey()},{$secondProduct->getKey()}",
            ])
            ->assertOk();
//            ->assertJsonCount(2, 'data');

        $this->assertElasticQuery([
            'bool' => [
                'must' =>  [],
                'should' => [],
                'filter' =>  [
                    [
                        'terms' => [
                            'id' => [
                                $firstProduct->getKey(),
                                $secondProduct->getKey(),
                            ],
                            'boost' => 1.0,
                        ],
                    ],
                    [
                        'term' => [
                            'public' => [
                                'value' => true,
                                'boost' => 1.0,
                            ],
                        ],
                    ],
                ],
            ],
        ]);
    }

    /**
     * @dataProvider authProvider
     */
    public function testIndexHidden($user): void
    {
        $this->$user->givePermissionTo(['products.show', 'products.show_hidden']);

        $product = Product::factory()->create([
            'public' => true,
        ]);
        $set = ProductSet::factory()->create([
            'public' => true,
            'hide_on_index' => true,
        ]);

        $product->sets()->sync([$set->getKey()]);

        $this->actingAs($this->$user)
            ->json('GET', '/products')
            ->assertOk();
//            ->assertJsonCount(3, 'data'); // Should show all products.

        $this->assertElasticQuery([
            'bool' => [
                'must' =>  [],
                'should' => [],
                'filter' =>  [],
            ],
        ]);
    }

    public function testShowUnauthorized(): void
    {
        $this->getJson('/products/' . $this->product->slug)
            ->assertForbidden();

        $this->getJson('/products/id:' . $this->product->getKey())
            ->assertForbidden();
    }

    /**
     * @dataProvider authProvider
     */
    public function testShow($user): void
    {
        $this->$user->givePermissionTo('products.show_details');

        $response = $this->actingAs($this->$user)
            ->getJson('/products/' . $this->product->slug);
        $response
            ->assertOk()
            ->assertJson(['data' => $this->expected]);

        $response = $this->actingAs($this->$user)
            ->getJson('/products/id:' . $this->product->getKey());
        $response
            ->assertOk()
            ->assertJson(['data' => $this->expected]);
    }

    /**
     * @dataProvider authProvider
     */
    public function testShowSets($user): void
    {
        $this->$user->givePermissionTo('products.show_details');

        $product = Product::factory()->create([
            'public' => true,
        ]);

        $set1 = ProductSet::factory()->create([
            'public' => true,
        ]);
        $set2 = ProductSet::factory()->create([
            'public' => true,
        ]);

        $product->sets()->sync([$set1->getKey(), $set2->getKey()]);

        $this
            ->actingAs($this->$user)
            ->getJson('/products/' . $product->slug)
            ->assertOk()
            ->assertJsonFragment(['sets' => [
                [
                    'id' => $set1->getKey(),
                    'name' => $set1->name,
                    'slug' => $set1->slug,
                    'slug_suffix' => $set1->slugSuffix,
                    'slug_override' => $set1->slugOverride,
                    'public' => $set1->public,
                    'visible' => $set1->public_parent && $set1->public,
                    'hide_on_index' => $set1->hide_on_index,
                    'parent_id' => $set1->parent_id,
                    'children_ids' => [],
                    'cover' => null,
                    'metadata' => [],
                ],
                [
                    'id' => $set2->getKey(),
                    'name' => $set2->name,
                    'slug' => $set2->slug,
                    'slug_suffix' => $set2->slugSuffix,
                    'slug_override' => $set2->slugOverride,
                    'public' => $set2->public,
                    'visible' => $set2->public_parent && $set2->public,
                    'hide_on_index' => $set2->hide_on_index,
                    'parent_id' => $set2->parent_id,
                    'children_ids' => [],
                    'cover' => null,
                    'metadata' => [],
                ],
            ],
            ]);
    }

    /**
     * @dataProvider authProvider
     */
    public function testShowSetsWithCover($user): void
    {
        $this->$user->givePermissionTo('products.show_details');

        $product = Product::factory()->create([
            'public' => true,
        ]);

        $media1 = Media::factory()->create([
            'type' => MediaType::PHOTO,
            'url' => 'https://picsum.photos/seed/' . rand(0, 999999) . '/800',
        ]);

        $media2 = Media::factory()->create([
            'type' => MediaType::PHOTO,
            'url' => 'https://picsum.photos/seed/' . rand(0, 999999) . '/800',
        ]);

        $set1 = ProductSet::factory()->create([
            'public' => true,
            'cover_id' => $media1->getKey(),
        ]);
        $set2 = ProductSet::factory()->create([
            'public' => true,
            'cover_id' => $media2->getKey(),
        ]);

        $product->sets()->sync([$set1->getKey(), $set2->getKey()]);

        $response = $this->actingAs($this->$user)
            ->getJson('/products/' . $product->slug);
        $response
            ->assertOk()
            ->assertJsonFragment(['sets' => [
                [
                    'id' => $set1->getKey(),
                    'name' => $set1->name,
                    'slug' => $set1->slug,
                    'slug_suffix' => $set1->slugSuffix,
                    'slug_override' => $set1->slugOverride,
                    'public' => $set1->public,
                    'visible' => $set1->public_parent && $set1->public,
                    'hide_on_index' => $set1->hide_on_index,
                    'parent_id' => $set1->parent_id,
                    'children_ids' => [],
                    'metadata' => [],
                    'cover' => [
                        'id' => $media1->getKey(),
                        'type' => Str::lower($media1->type->key),
                        'url' => $media1->url,
                        'slug' => $media1->slug,
                        'alt' => $media1->alt,
                        'metadata' => [],
                    ],
                ],
                [
                    'id' => $set2->getKey(),
                    'name' => $set2->name,
                    'slug' => $set2->slug,
                    'slug_suffix' => $set2->slugSuffix,
                    'slug_override' => $set2->slugOverride,
                    'public' => $set2->public,
                    'visible' => $set2->public_parent && $set2->public,
                    'hide_on_index' => $set2->hide_on_index,
                    'parent_id' => $set2->parent_id,
                    'children_ids' => [],
                    'metadata' => [],
                    'cover' => [
                        'id' => $media2->getKey(),
                        'type' => Str::lower($media2->type->key),
                        'url' => $media2->url,
                        'slug' => $media2->slug,
                        'alt' => $media2->alt,
                        'metadata' => [],
                    ],
                ],
            ],
            ]);
    }

    /**
     * @dataProvider authProvider
     */
    public function testShowAttributes($user): void
    {
        $this->$user->givePermissionTo('products.show_details');

        $product = Product::factory()->create([
            'public' => true,
        ]);

        $attribute = Attribute::factory()->create();

        $option = AttributeOption::factory()->create([
            'index' => 1,
            'attribute_id' => $attribute->getKey()
        ]);

        $product->attributes()->attach($attribute->getKey());

        $product->attributes->first()->pivot->options()->attach($option->getKey());

        $this
            ->actingAs($this->$user)
            ->getJson('/products/' . $product->slug)
            ->assertOk()
            ->assertJsonFragment([
                'id' => $attribute->getKey(),
                'name' => $attribute->name,
                'description' => $attribute->description,
                'type' => $attribute->type,
                'global' => $attribute->global
            ])
            ->assertJsonFragment([
                'id' => $option->getKey(),
                'name' => $option->name,
                'index' => $option->index,
                'value_number' => $option->value_number,
                'value_date' => $option->value_date,
                'attribute_id' => $attribute->getKey(),
            ]);
    }

    /**
     * @dataProvider authProvider
     */
    public function testShowPrivateMetadata($user): void
    {
        $this->$user->givePermissionTo(['products.show_details', 'products.show_metadata_private']);

        $privateMetadata = $this->product->metadataPrivate()->create([
            'name' => 'hiddenMetadata',
            'value' => 'hidden metadata test',
            'value_type' => MetadataType::STRING,
            'public' => false,
        ]);

        $response = $this->actingAs($this->$user)
            ->getJson('/products/id:' . $this->product->getKey());

        $response
            ->assertOk()
            ->assertJsonFragment(['metadata_private' => [
                $privateMetadata->name => $privateMetadata->value,
            ]]);
    }

    /**
     * @dataProvider authProvider
     */
    public function testShowHiddenUnauthorized($user): void
    {
        $this->$user->givePermissionTo('products.show_details');

        $response = $this->actingAs($this->$user)
            ->getJson('/products/' . $this->hidden_product->slug);
        $response->assertNotFound();

        $response = $this->actingAs($this->$user)
            ->getJson('/products/id:' . $this->hidden_product->getKey());
        $response->assertNotFound();
    }

    /**
     * Sets shouldn't affect product visibility
     *
     * @dataProvider authProvider
     */
    public function testShowHiddenWithPublicSetUnauthorized($user): void
    {
        $this->$user->givePermissionTo('products.show_details');

        /** @var ProductSet $publicSet */
        $publicSet = ProductSet::factory()->create([
            'public' => true,
        ]);

        $publicSet->products()->attach($this->hidden_product);

        $response = $this->actingAs($this->$user)
            ->getJson('/products/' . $this->hidden_product->slug);
        $response->assertNotFound();

        $response = $this->actingAs($this->$user)
            ->getJson('/products/id:' . $this->hidden_product->getKey());
        $response->assertNotFound();
    }

    /**
     * @dataProvider authProvider
     */
    public function testShowHidden($user): void
    {
        $this->$user->givePermissionTo(['products.show_details', 'products.show_hidden']);

        $response = $this->actingAs($this->$user)
            ->getJson('/products/' . $this->hidden_product->slug);
        $response->assertOk();

        $response = $this->actingAs($this->$user)
            ->getJson('/products/id:' . $this->hidden_product->getKey());
        $response->assertOk();
    }

    public function noIndexProvider(): array
    {
        return [
            'as user no index' => ['user', true],
            'as application no index' => ['application', true],
            'as user index' => ['user', false],
            'as application index' => ['application', false],
        ];
    }

    /**
     * @dataProvider noIndexProvider
     */
    public function testShowSeoNoIndex($user, $noIndex): void
    {
        $this->$user->givePermissionTo('products.show_details');

        $product = Product::factory([
            'public' => true,
        ])->create();

        $seo = SeoMetadata::factory([
            'no_index' => $noIndex,
        ])->create();

        $product->seo()->save($seo);

        $response = $this->actingAs($this->$user)
            ->getJson('/products/id:' . $product->getKey());
        $response
            ->assertOk()
            ->assertJsonFragment(['seo' => [
                'title' => $seo->title,
                'no_index' => $noIndex,
                'description' => $seo->description,
                'og_image' => null,
                'twitter_card' => $seo->twitter_card,
                'keywords' => $seo->keywords,
            ],
            ]);
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
    public function testCreate($user): void
    {
        $this->$user->givePermissionTo('products.add');

        Queue::fake();

        $response = $this->actingAs($this->$user)->postJson('/products', [
            'name' => 'Test',
            'slug' => 'test',
            'price' => 100.00,
            'description_html' => '<h1>Description</h1>',
            'description_short' => 'So called short description...',
            'public' => true,
        ]);

        $response
            ->assertCreated()
            ->assertJson(['data' => [
                'slug' => 'test',
                'name' => 'Test',
                'price' => 100,
                'public' => true,
                'description_html' => '<h1>Description</h1>',
                'description_short' => 'So called short description...',
                'cover' => null,
                'gallery' => [],
            ],
            ]);

        $this->assertDatabaseHas('products', [
            'slug' => 'test',
            'name' => 'Test',
            'price' => 100,
            'public' => true,
            'description_html' => '<h1>Description</h1>',
            'description_short' => 'So called short description...',
        ]);

        Queue::assertPushed(CallQueuedListener::class, function ($job) {
            return $job->class === WebHookEventListener::class
                && $job->data[0] instanceof ProductCreated;
        });

        $product = Product::find($response->getData()->data->id);
        $event = new ProductCreated($product);
        $listener = new WebHookEventListener();

        $listener->handle($event);

        Queue::assertNotPushed(CallWebhookJob::class);
    }

    /**
     * @dataProvider authProvider
     */
    public function testCreateWithWebHookQueue($user): void
    {
        $this->$user->givePermissionTo('products.add');

        $webHook = WebHook::factory()->create([
            'events' => [
                'ProductCreated',
            ],
            'model_type' => $this->$user::class,
            'creator_id' => $this->$user->getKey(),
            'with_issuer' => false,
            'with_hidden' => false,
        ]);

        Queue::fake();

        $response = $this->actingAs($this->$user)->postJson('/products', [
            'name' => 'Test',
            'slug' => 'test',
            'price' => 100.00,
            'description_html' => '<h1>Description</h1>',
            'public' => true,
        ]);

        $response
            ->assertCreated()
            ->assertJson(['data' => [
                'slug' => 'test',
                'name' => 'Test',
                'price' => 100,
                'public' => true,
                'description_html' => '<h1>Description</h1>',
                'cover' => null,
                'gallery' => [],
            ],
            ]);

        $this->assertDatabaseHas('products', [
            'slug' => 'test',
            'name' => 'Test',
            'price' => 100,
            'public' => true,
            'description_html' => '<h1>Description</h1>',
        ]);

        Queue::assertPushed(CallQueuedListener::class, function ($job) {
            return $job->class === WebHookEventListener::class
                && $job->data[0] instanceof ProductCreated;
        });

        $product = Product::find($response->getData()->data->id);
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
    public function testCreateWithWebHookDispatched($user): void
    {
        $this->$user->givePermissionTo('products.add');

        $webHook = WebHook::factory()->create([
            'events' => [
                'ProductCreated',
            ],
            'model_type' => $this->$user::class,
            'creator_id' => $this->$user->getKey(),
            'with_issuer' => false,
            'with_hidden' => false,
        ]);

        Bus::fake();

        $response = $this->actingAs($this->$user)->postJson('/products', [
            'name' => 'Test',
            'slug' => 'test',
            'price' => 100.00,
            'description_html' => '<h1>Description</h1>',
            'public' => true,
        ]);

        $response
            ->assertCreated()
            ->assertJson(['data' => [
                'slug' => 'test',
                'name' => 'Test',
                'price' => 100,
                'public' => true,
                'description_html' => '<h1>Description</h1>',
                'cover' => null,
                'gallery' => [],
            ],
            ]);

        $this->assertDatabaseHas('products', [
            'slug' => 'test',
            'name' => 'Test',
            'price' => 100,
            'public' => true,
            'description_html' => '<h1>Description</h1>',
        ]);

        Bus::assertDispatched(CallQueuedListener::class, function ($job) {
            return $job->class === WebHookEventListener::class
                && $job->data[0] instanceof ProductCreated;
        });

        $product = Product::find($response->getData()->data->id);
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
    public function testCreateHiddenWithWebHookWithoutHidden($user): void
    {
        $this->$user->givePermissionTo('products.add');

        $webHook = WebHook::factory()->create([
            'events' => [
                'ProductCreated',
            ],
            'model_type' => $this->$user::class,
            'creator_id' => $this->$user->getKey(),
            'with_issuer' => false,
            'with_hidden' => false,
        ]);

        Queue::fake();

        $response = $this->actingAs($this->$user)->postJson('/products', [
            'name' => 'Test',
            'slug' => 'test',
            'price' => 100.00,
            'description_html' => '<h1>Description</h1>',
            'public' => false,
        ]);

        $response
            ->assertCreated()
            ->assertJson(['data' => [
                'slug' => 'test',
                'name' => 'Test',
                'price' => 100,
                'public' => false,
                'description_html' => '<h1>Description</h1>',
                'cover' => null,
                'gallery' => [],
            ],
            ]);

        $this->assertDatabaseHas('products', [
            'slug' => 'test',
            'name' => 'Test',
            'price' => 100,
            'public' => false,
            'description_html' => '<h1>Description</h1>',
        ]);

        Queue::assertPushed(CallQueuedListener::class, function ($job) {
            return $job->class === WebHookEventListener::class
                && $job->data[0] instanceof ProductCreated;
        });

        $product = Product::find($response->getData()->data->id);
        $event = new ProductCreated($product);
        $listener = new WebHookEventListener();

        $listener->handle($event);

        Queue::assertNotPushed(CallWebhookJob::class);
    }

    /**
     * @dataProvider authProvider
     */
    public function testCreateHiddenWithWebHook($user): void
    {
        $this->$user->givePermissionTo('products.add');

        $webHook = WebHook::factory()->create([
            'events' => [
                'ProductCreated',
            ],
            'model_type' => $this->$user::class,
            'creator_id' => $this->$user->getKey(),
            'with_issuer' => false,
            'with_hidden' => true,
        ]);

        Bus::fake();

        $response = $this->actingAs($this->$user)->postJson('/products', [
            'name' => 'Test',
            'slug' => 'test',
            'price' => 100.00,
            'description_html' => '<h1>Description</h1>',
            'public' => false,
        ]);

        $response
            ->assertCreated()
            ->assertJson(['data' => [
                'slug' => 'test',
                'name' => 'Test',
                'price' => 100,
                'public' => false,
                'description_html' => '<h1>Description</h1>',
                'cover' => null,
                'gallery' => [],
            ],
            ]);

        $this->assertDatabaseHas('products', [
            'slug' => 'test',
            'name' => 'Test',
            'price' => 100,
            'public' => false,
            'description_html' => '<h1>Description</h1>',
        ]);

        Bus::assertDispatched(CallQueuedListener::class, function ($job) {
            return $job->class = WebHookEventListener::class;
        });

        $product = Product::find($response->getData()->data->id);
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
    public function testCreateWithZeroPrice($user): void
    {
        $this->$user->givePermissionTo('products.add');

        $this
            ->actingAs($this->$user)
            ->postJson('/products', [
                'name' => 'Test',
                'slug' => 'test',
                'price' => 0,
                'public' => true,
            ])
            ->assertCreated();

        $this->assertDatabaseHas('products', [
            'slug' => 'test',
            'name' => 'Test',
            'price' => 0,
        ]);
    }

    /**
     * @dataProvider authProvider
     */
    public function testCreateWithNegativePrice($user): void
    {
        $this->$user->givePermissionTo('products.add');

        $this
            ->actingAs($this->$user)
            ->postJson('/products', [
                'name' => 'Test',
                'slug' => 'test',
                'price' => -100,
                'public' => true,
            ])
            ->assertUnprocessable();
    }

    /**
     * @dataProvider authProvider
     */
    public function testCreateWithSchemas($user): void
    {
        $this->$user->givePermissionTo('products.add');

        Event::fake([ProductCreated::class]);

        $schema = Schema::factory()->create();

        $response = $this->actingAs($this->$user)->postJson('/products', [
            'name' => 'Test',
            'slug' => 'test',
            'price' => 150,
            'public' => false,
            'schemas' => [
                $schema->getKey(),
            ],
        ]);

        $response->assertCreated();
        $product = $response->getData()->data;

        $this->assertDatabaseHas('products', [
            'slug' => 'test',
            'name' => 'Test',
            'price' => 150,
            'public' => false,
            'description_html' => null,
        ]);

        $this->assertDatabaseHas('product_schemas', [
            'product_id' => $product->id,
            'schema_id' => $schema->id,
        ]);

        Event::assertDispatched(ProductCreated::class);
    }

    /**
     * @dataProvider authProvider
     */
    public function testCreateWithSets($user): void
    {
        $this->$user->givePermissionTo('products.add');

        Event::fake([ProductCreated::class]);

        $set1 = ProductSet::factory()->create();
        $set2 = ProductSet::factory()->create();

        $response = $this->actingAs($this->$user)->postJson('/products', [
            'name' => 'Test',
            'slug' => 'test',
            'price' => 150,
            'public' => false,
            'sets' => [
                $set1->getKey(),
                $set2->getKey(),
            ],
        ]);

        $response->assertCreated();
        $product = $response->getData()->data;

        $this->assertDatabaseHas('products', [
            'slug' => 'test',
            'name' => 'Test',
            'price' => 150,
            'public' => false,
            'description_html' => null,
        ]);

        $this->assertDatabaseHas('product_set_product', [
            'product_id' => $product->id,
            'product_set_id' => $set1->getKey(),
        ]);

        $this->assertDatabaseHas('product_set_product', [
            'product_id' => $product->id,
            'product_set_id' => $set2->getKey(),
        ]);

        Event::assertDispatched(ProductCreated::class);
    }

    /**
     * @dataProvider authProvider
     */
    public function testCreateWithSeo($user): void
    {
        $this->$user->givePermissionTo('products.add');

        $media = Media::factory()->create([
            'type' => MediaType::PHOTO,
            'url' => 'https://picsum.photos/seed/' . rand(0, 999999) . '/800',
        ]);

        $response = $this->actingAs($this->$user)->json('POST', '/products', [
            'name' => 'Test',
            'slug' => 'test',
            'price' => 100.00,
            'description_html' => '<h1>Description</h1>',
            'public' => true,
            'seo' => [
                'title' => 'seo title',
                'description' => 'seo description',
                'og_image_id' => $media->getKey(),
                'no_index' => true,
            ],
        ]);

        $response
            ->assertCreated()
            ->assertJson(['data' => [
                'slug' => 'test',
                'name' => 'Test',
                'price' => 100,
                'public' => true,
                'description_html' => '<h1>Description</h1>',
                'cover' => null,
                'gallery' => [],
                'seo' => [
                    'title' => 'seo title',
                    'description' => 'seo description',
                    'og_image' => [
                        'id' => $media->getKey(),
                    ],
                    'no_index' => true,
                ],
            ],
            ]);

        $this->assertDatabaseHas('products', [
            'slug' => 'test',
            'name' => 'Test',
            'price' => 100,
            'public' => true,
            'description_html' => '<h1>Description</h1>',
        ]);

        $this->assertDatabaseHas('seo_metadata', [
            'title' => 'seo title',
            'description' => 'seo description',
            'model_id' => $response->getData()->data->id,
            'model_type' => Product::class,
            'no_index' => true,
        ]);

        $this->assertDatabaseCount('seo_metadata', 2);
    }

    /**
     * @dataProvider authProvider
     */
    public function testCreateWithSeoDefaultIndex($user): void
    {
        $this->$user->givePermissionTo('products.add');

        $response = $this->actingAs($this->$user)->json('POST', '/products', [
            'name' => 'Test',
            'slug' => 'test',
            'price' => 100.00,
            'description_html' => '<h1>Description</h1>',
            'public' => true,
            'seo' => [
                'title' => 'seo title',
                'description' => 'seo description',
            ],
        ]);

        $response
            ->assertCreated()
            ->assertJson(['data' => [
                'slug' => 'test',
                'name' => 'Test',
                'price' => 100,
                'public' => true,
                'description_html' => '<h1>Description</h1>',
                'cover' => null,
                'gallery' => [],
                'seo' => [
                    'title' => 'seo title',
                    'description' => 'seo description',
                    'no_index' => false,
                ],
            ],
            ]);

        $this->assertDatabaseHas('products', [
            'slug' => 'test',
            'name' => 'Test',
            'price' => 100,
            'public' => true,
            'description_html' => '<h1>Description</h1>',
        ]);

        $this->assertDatabaseHas('seo_metadata', [
            'title' => 'seo title',
            'description' => 'seo description',
            'model_id' => $response->getData()->data->id,
            'model_type' => Product::class,
            'no_index' => false,
        ]);

        $this->assertDatabaseCount('seo_metadata', 2);
    }

    /**
     * @dataProvider authProvider
     */
    public function testCreateMinMaxPrice($user): void
    {
        $this->$user->givePermissionTo('products.add');

        $schemaPrice = 50;
        $schema = Schema::factory()->create([
            'type' => SchemaType::STRING,
            'required' => false,
            'price' => $schemaPrice,
        ]);

        $productPrice = 150;
        $response = $this->actingAs($this->$user)->postJson('/products', [
            'name' => 'Test',
            'slug' => 'test',
            'price' => $productPrice,
            'public' => false,
            'sets' => [],
            'schemas' => [
                $schema->getKey(),
            ],
        ]);

        $response->assertCreated();

        $this->assertDatabaseHas('products', [
            'slug' => 'test',
            'name' => 'Test',
            'price' => $productPrice,
            'price_min' => $productPrice,
            'price_max' => $productPrice + $schemaPrice,
            'public' => false,
            'description_html' => null,
        ]);
    }

    /**
     * @dataProvider authProvider
     */
    public function testCreateMinPriceWithRequiredSchema($user): void
    {
        $this->$user->givePermissionTo('products.add');

        $schemaPrice = 50;
        $schema = Schema::factory()->create([
            'type' => SchemaType::STRING,
            'required' => true,
            'price' => $schemaPrice,
        ]);

        $productPrice = 150;
        $response = $this->actingAs($this->$user)->postJson('/products', [
            'name' => 'Test',
            'slug' => 'test',
            'price' => $productPrice,
            'public' => false,
            'sets' => [],
            'schemas' => [
                $schema->getKey(),
            ],
        ]);

        $response->assertCreated();

        $this->assertDatabaseHas('products', [
            'slug' => 'test',
            'name' => 'Test',
            'price' => $productPrice,
            'price_min' => $productPrice + $schemaPrice,
            'price_max' => $productPrice + $schemaPrice,
            'public' => false,
            'description_html' => null,
        ]);
    }

    /**
     * @dataProvider authProvider
     */
    public function testCreateWithAttribute($user): void
    {
        $this->$user->givePermissionTo('products.add');

        $attribute = Attribute::factory()->create();

        $option = AttributeOption::factory()->create([
            'index' => 1,
            'attribute_id' => $attribute->getKey()
        ]);

        $attribute2 = Attribute::factory()->create();

        $option2 = AttributeOption::factory()->create([
            'index' => 2,
            'attribute_id' => $attribute2->getKey()
        ]);

        $response = $this
            ->actingAs($this->$user)
            ->postJson('/products', [
                'name' => 'Test',
                'slug' => 'test',
                'price' => 0,
                'public' => true,
                'attributes' => [
                    $attribute->getKey() => $option->getKey(),
                    $attribute2->getKey() => $option2->getKey(),
                ]
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
            'name' => 'Test',
            'price' => 0,
        ]);

        $product = Product::find($response->getData()->data->id);

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
            'attribute_option_id' => $option->getKey()
        ]);

        $this->assertDatabaseHas('product_attribute_attribute_option', [
            'product_attribute_id' => $productAttribute2->getKey(),
            'attribute_option_id' => $option2->getKey()
        ]);

        $this->assertDatabaseCount('product_attribute_attribute_option', 3); // +1 from $this->product
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
    public function testUpdate($user): void
    {
        $this->$user->givePermissionTo('products.edit');

        Queue::fake();

        $response = $this->actingAs($this->$user)->patchJson('/products/id:' . $this->product->getKey(), [
            'name' => 'Updated',
            'slug' => 'updated',
            'price' => 150,
            'description_html' => '<h1>New description</h1>',
            'description_short' => 'New so called short description',
            'public' => false,
        ]);

        $response->assertOk();

        $this->assertDatabaseHas('products', [
            'id' => $this->product->getKey(),
            'name' => 'Updated',
            'slug' => 'updated',
            'price' => 150,
            'description_html' => '<h1>New description</h1>',
            'description_short' => 'New so called short description',
            'public' => false,
        ]);

        Queue::assertPushed(CallQueuedListener::class, function ($job) {
            return $job->class === WebHookEventListener::class
                && $job->data[0] instanceof ProductUpdated;
        });

        $product = Product::find($this->product->getKey());
        $event = new ProductUpdated($product);
        $listener = new WebHookEventListener();

        $listener->handle($event);

        Queue::assertNotPushed(CallWebhookJob::class);
    }

    /**
     * @dataProvider authProvider
     */
    public function testUpdateWithWebHookQueue($user): void
    {
        $this->$user->givePermissionTo('products.edit');

        $webHook = WebHook::factory()->create([
            'events' => [
                'ProductUpdated',
            ],
            'model_type' => $this->$user::class,
            'creator_id' => $this->$user->getKey(),
            'with_issuer' => false,
            'with_hidden' => true,
        ]);

        Queue::fake();

        $response = $this->actingAs($this->$user)->patchJson('/products/id:' . $this->product->getKey(), [
            'name' => 'Updated',
            'slug' => 'updated',
            'price' => 150,
            'description_html' => '<h1>New description</h1>',
            'public' => false,
        ]);

        $response->assertOk();

        $this->assertDatabaseHas('products', [
            'id' => $this->product->getKey(),
            'name' => 'Updated',
            'slug' => 'updated',
            'price' => 150,
            'description_html' => '<h1>New description</h1>',
            'public' => false,
        ]);

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
    public function testUpdateWithWebHookDispatched($user): void
    {
        $this->$user->givePermissionTo('products.edit');

        $webHook = WebHook::factory()->create([
            'events' => [
                'ProductUpdated',
            ],
            'model_type' => $this->$user::class,
            'creator_id' => $this->$user->getKey(),
            'with_issuer' => false,
            'with_hidden' => true,
        ]);

        Bus::fake();

        $response = $this->actingAs($this->$user)->patchJson('/products/id:' . $this->product->getKey(), [
            'name' => 'Updated',
            'slug' => 'updated',
            'price' => 150,
            'description_html' => '<h1>New description</h1>',
            'public' => false,
        ]);

        $response->assertOk();

        $this->assertDatabaseHas('products', [
            'id' => $this->product->getKey(),
            'name' => 'Updated',
            'slug' => 'updated',
            'price' => 150,
            'description_html' => '<h1>New description</h1>',
            'public' => false,
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
    public function testUpdateChangeSets($user): void
    {
        $this->$user->givePermissionTo('products.edit');

        Event::fake([ProductUpdated::class]);

        $product = Product::factory()->create();

        $set1 = ProductSet::factory()->create();
        $set2 = ProductSet::factory()->create();
        $set3 = ProductSet::factory()->create();

        $product->sets()->sync([$set1->getKey(), $set2->getKey()]);

        $response = $this->actingAs($this->$user)->patchJson('/products/id:' . $product->getKey(), [
            'name' => $product->name,
            'slug' => $product->slug,
            'price' => $product->price,
            'public' => $product->public,
            'sets' => [
                $set2->getKey(),
                $set3->getKey(),
            ],
        ]);

        $response->assertOk();

        $this->assertDatabaseHas('product_set_product', [
            'product_id' => $product->getKey(),
            'product_set_id' => $set2->getKey(),
        ]);

        $this->assertDatabaseHas('product_set_product', [
            'product_id' => $product->getKey(),
            'product_set_id' => $set3->getKey(),
        ]);

        $this->assertDatabaseMissing('product_set_product', [
            'product_id' => $product->getKey(),
            'product_set_id' => $set1->getKey(),
        ]);

        Event::assertDispatched(ProductUpdated::class);
    }

    /**
     * @dataProvider authProvider
     */
    public function testUpdateDeleteSets($user): void
    {
        $this->$user->givePermissionTo('products.edit');

        Event::fake([ProductUpdated::class]);

        $product = Product::factory()->create();

        $set1 = ProductSet::factory()->create();
        $set2 = ProductSet::factory()->create();

        $product->sets()->sync([$set1->getKey(), $set2->getKey()]);

        $response = $this->actingAs($this->$user)->patchJson('/products/id:' . $product->getKey(), [
            'name' => $product->name,
            'slug' => $product->slug,
            'price' => $product->price,
            'public' => $product->public,
            'sets' => [],
        ]);

        $response->assertOk();

        $this->assertDatabaseMissing('product_set_product', [
            'product_id' => $product->getKey(),
        ]);

        Event::assertDispatched(ProductUpdated::class);
    }

    /**
     * @dataProvider authProvider
     */
    public function testUpdateWithSeo($user): void
    {
        $this->$user->givePermissionTo('products.edit');

        $product = Product::factory([
            'name' => 'Created',
            'slug' => 'created',
            'price' => 100,
            'description_html' => '<h1>Description</h1>',
            'public' => false,
            'order' => 1,
        ])->create();

        $seo = SeoMetadata::factory()->create();
        $product->seo()->save($seo);

        $response = $this->actingAs($this->$user)->json('PATCH', '/products/id:' . $product->getKey(), [
            'name' => 'Updated',
            'slug' => 'updated',
            'price' => 150,
            'description_html' => '<h1>New description</h1>',
            'public' => false,
            'seo' => [
                'title' => 'seo title',
                'description' => 'seo description',
            ],
        ]);

        $response->assertOk();

        $this->assertDatabaseHas('products', [
            'id' => $product->getKey(),
            'name' => 'Updated',
            'slug' => 'updated',
            'price' => 150,
            'description_html' => '<h1>New description</h1>',
            'public' => false,
        ]);

        $this->assertDatabaseHas('seo_metadata', [
            'title' => 'seo title',
            'description' => 'seo description',
        ]);
    }

    /**
     * @dataProvider authProvider
     */
    public function testUpdateMinMaxPrice($user): void
    {
        $this->$user->givePermissionTo('products.edit');

        $productPrice = 150;
        $product = Product::factory()->create([
            'price' => $productPrice,
        ]);

        $schemaPrice = 50;
        $schema = Schema::factory()->create([
            'type' => 0,
            'required' => false,
            'price' => $schemaPrice,
        ]);

        $product->schemas()->attach($schema->getKey());
        $this->productService->updateMinMaxPrices($product);

        $productNewPrice = 250;
        $response = $this->actingAs($this->$user)->patchJson('/products/id:' . $product->getKey(), [
            'name' => $product->name,
            'slug' => $product->slug,
            'price' => $product->price,
            'public' => $product->public,
            'price' => $productNewPrice,
            'sets' => [],
            'schemas' => [
                $schema->getKey(),
            ],
        ]);

        $response->assertOk();

        $this->assertDatabaseHas('products', [
            $product->getKeyName() => $product->getKey(),
            'price' => $productNewPrice,
            'price_min' => $productNewPrice,
            'price_max' => $productNewPrice + $schemaPrice,
        ]);
    }

    /**
     * @dataProvider authProvider
     */
    public function testUpdateSchemaMinMaxPrice($user): void
    {
        $this->$user->givePermissionTo('products.edit');

        $productPrice = 150;
        $product = Product::factory()->create([
            'price' => $productPrice,
        ]);

        $schemaPrice = 50;
        $schema = Schema::factory()->create([
            'type' => 0,
            'required' => true,
            'price' => $schemaPrice,
        ]);

        $product->schemas()->attach($schema->getKey());
        $this->productService->updateMinMaxPrices($product);

        $schemaNewPrice = 75;
        $response = $this->actingAs($this->$user)->patchJson('/schemas/id:' . $schema->getKey(), [
            'name' => 'Test Updated',
            'price' => $schemaNewPrice,
            'type' => 'string',
            'required' => false,
        ]);

        $response->assertOk();

        $this->assertDatabaseHas('products', [
            $product->getKeyName() => $product->getKey(),
            'price' => $productPrice,
            'price_min' => $productPrice,
            'price_max' => $productPrice + $schemaNewPrice,
        ]);
    }

    /**
     * @dataProvider authProvider
     */
    public function testDeleteSchemaMinMaxPrice($user): void
    {
        $this->$user->givePermissionTo('schemas.remove');

        $productPrice = 150;
        $product = Product::factory()->create([
            'price' => $productPrice,
        ]);

        $schemaPrice = 50;
        $schema = Schema::factory()->create([
            'type' => 0,
            'required' => true,
            'price' => $schemaPrice,
        ]);

        $product->schemas()->attach($schema->getKey());
        $this->productService->updateMinMaxPrices($product);

        $response = $this->actingAs($this->$user)->deleteJson('/schemas/id:' . $schema->getKey());

        $response->assertNoContent();

        $this->assertDatabaseHas('products', [
            $product->getKeyName() => $product->getKey(),
            'price' => $productPrice,
            'price_min' => $productPrice,
            'price_max' => $productPrice,
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
    public function testDelete($user): void
    {
        $this->$user->givePermissionTo('products.remove');

        Queue::fake();
        $product = Product::factory([
            'name' => 'Created',
            'slug' => 'created',
            'price' => 100,
            'description_html' => '<h1>Description</h1>',
            'public' => false,
            'order' => 1,
        ])->create();

        $seo = SeoMetadata::factory()->create();
        $product->seo()->save($seo);

        $response = $this->actingAs($this->$user)
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
    public function testDeleteWithMedia($user): void
    {
        $this->$user->givePermissionTo('products.remove');

        $media = Media::factory()->create([
            'type' => MediaType::PHOTO,
            'url' => 'https://picsum.photos/seed/' . rand(0, 999999) . '/800',
        ]);

        $product = Product::factory([
            'name' => 'Delete with media',
            'slug' => 'Delete-with-media',
            'price' => 100,
            'description_html' => '<h1>Description</h1>',
            'public' => false,
            'order' => 1,
        ])->create();

        $product->media()->sync($media);

        Http::fake(['*' => Http::response(status: 204)]);

        $response = $this->actingAs($this->$user)
            ->deleteJson('/products/id:' . $product->getKey());
        $response->assertNoContent();
        $this->assertSoftDeleted($product);
        $this->assertModelMissing($media);
    }

    /**
     * @dataProvider authProvider
     */
    public function testDeleteWithWebHookQueue($user): void
    {
        $this->$user->givePermissionTo('products.remove');

        $webHook = WebHook::factory()->create([
            'events' => [
                'ProductDeleted',
            ],
            'model_type' => $this->$user::class,
            'creator_id' => $this->$user->getKey(),
            'with_issuer' => false,
            'with_hidden' => false,
        ]);

        Queue::fake();

        $response = $this->actingAs($this->$user)
            ->deleteJson('/products/id:' . $this->product->getKey());

        Queue::assertPushed(CallQueuedListener::class, function ($job) {
            return $job->class === WebHookEventListener::class
                && $job->data[0] instanceof ProductDeleted;
        });

        $response->assertNoContent();
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
    public function testDeleteWithWebHookDispatched($user): void
    {
        $this->$user->givePermissionTo('products.remove');

        $webHook = WebHook::factory()->create([
            'events' => [
                'ProductDeleted',
            ],
            'model_type' => $this->$user::class,
            'creator_id' => $this->$user->getKey(),
            'with_issuer' => false,
            'with_hidden' => false,
        ]);

        Bus::fake();

        $response = $this->actingAs($this->$user)
            ->deleteJson('/products/id:' . $this->product->getKey());

        Bus::assertDispatched(CallQueuedListener::class, function ($job) {
            return $job->class === WebHookEventListener::class
                && $job->data[0] instanceof ProductDeleted;
        });

        $response->assertNoContent();
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

    /**
     * @dataProvider authProvider
     */
    public function testIndexSetsDefaultAsArray($user): void
    {
        $this->$user->givePermissionTo('products.show');

        $response = $this->actingAs($this->$user)->json('GET', '/products?full=1&sets=');
        $response
            ->assertOk()
            ->assertJsonCount(0, 'data')
            ->assertJson(['data' => []]);
    }
}
