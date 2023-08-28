<?php

namespace Tests\Feature;

use App\Enums\ConditionType;
use App\Enums\DiscountTargetType;
use App\Enums\DiscountType;
use App\Enums\MediaType;
use App\Enums\Product\ProductPriceType;
use App\Enums\SchemaType;
use App\Events\ProductCreated;
use App\Events\ProductDeleted;
use App\Events\ProductPriceUpdated;
use App\Events\ProductUpdated;
use App\Listeners\WebHookEventListener;
use App\Models\ConditionGroup;
use App\Models\Discount;
use App\Models\Media;
use App\Models\Price;
use App\Models\Product;
use App\Models\ProductAttribute;
use App\Models\Schema;
use App\Models\WebHook;
use App\Repositories\Contracts\ProductRepositoryContract;
use App\Services\Contracts\AvailabilityServiceContract;
use App\Services\Contracts\DiscountServiceContract;
use App\Services\ProductService;
use App\Services\SchemaCrudService;
use Brick\Math\Exception\NumberFormatException;
use Brick\Math\Exception\RoundingNecessaryException;
use Brick\Money\Exception\UnknownCurrencyException;
use Brick\Money\Money;
use Domain\Currency\Currency;
use Domain\Language\Language;
use Domain\Metadata\Enums\MetadataType;
use Domain\Price\Dtos\PriceDto;
use Domain\ProductAttribute\Enums\AttributeType;
use Domain\ProductAttribute\Models\Attribute;
use Domain\ProductAttribute\Models\AttributeOption;
use Domain\ProductSet\ProductSet;
use Domain\Seo\Models\SeoMetadata;
use Heseya\Dto\DtoException;
use Illuminate\Events\CallQueuedListener;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Ramsey\Uuid\Uuid;
use Spatie\WebhookServer\CallWebhookJob;
use Tests\TestCase;
use Tests\Utils\FakeDto;

class ProductTest extends TestCase
{
    private Product $product;
    private Product $hidden_product;

    private array $expected;
    private array $expected_short;

    private Currency $currency;
    private Product $saleProduct;
    private array $productPrices;

    private ProductService $productService;
    private DiscountServiceContract $discountService;
    private ProductRepositoryContract $productRepository;
    private SchemaCrudService $schemaCrudService;

    /**
     * @throws UnknownCurrencyException
     * @throws DtoException
     * @throws RoundingNecessaryException
     * @throws NumberFormatException
     */
    public function setUp(): void
    {
        parent::setUp();

        $this->currency = Currency::DEFAULT;

        $this->productService = App::make(ProductService::class);
        $this->discountService = App::make(DiscountServiceContract::class);
        $this->productRepository = App::make(ProductRepositoryContract::class);
        $this->schemaCrudService = App::make(SchemaCrudService::class);

        $this->productPrices = array_map(fn (Currency $currency) => [
            'value' => '100.00',
            'currency' => $currency->value,
        ], Currency::cases());

        /** @var AvailabilityServiceContract $availabilityService */
        $availabilityService = App::make(AvailabilityServiceContract::class);

        $this->product = $this->productService->create(FakeDto::productCreateDto([
            'shipping_digital' => false,
            'public' => true,
            'order' => 1,
            'prices_base' => $this->productPrices,
        ]));

        $schema = $this->schemaCrudService->store(FakeDto::schemaDto([
            'name' => 'Rozmiar',
            'type' => SchemaType::SELECT,
            'prices' => [PriceDto::from(Money::of(0, $this->currency->value))],
            'required' => true,
        ]));
        $this->product->schemas()->attach($schema->getKey());

        $this->travel(5)->hours();

        $l = $schema->options()->create([
            'name' => 'L',
            'order' => 2,
        ]);
        $l->prices()->createMany(
            Price::factory(['value' => 0])->prepareForCreateMany(),
        );

        $l->items()->create([
            'name' => 'Koszulka L',
            'sku' => 'K001/L',
        ]);

        $this->travelBack();

        $xl = $schema->options()->create([
            'name' => 'XL',
            'order' => 1,
        ]);
        $xl->prices()->createMany(
            Price::factory(['value' => 0])->prepareForCreateMany(),
        );

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
            'public' => true,
        ]);

        $this->hidden_product = Product::factory()->create([
            'public' => false,
        ]);

        $attribute = Attribute::factory()->create();

        $option = AttributeOption::factory()->create([
            'index' => 1,
            'attribute_id' => $attribute->getKey(),
        ]);

        $this->product->attributes()->attach($attribute->getKey());
        $this->product->attributes->first()->pivot->options()->attach($option->getKey());

        $availabilityService->calculateItemAvailability($item);

        // Expected short response
        $this->expected_short = [
            'id' => $this->product->getKey(),
            'name' => $this->product->name,
            'slug' => $this->product->slug,
            'visible' => $this->product->public,
            'public' => (bool) $this->product->public,
            'available' => true,
            'cover' => null,
        ];

        $expected_attribute_short = [
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
                ],
            ],
        ];

        $expected_attribute = $expected_attribute_short;
        $expected_attribute['attributes'][0] += [
            'id' => $attribute->getKey(),
            'slug' => $attribute->slug,
            'description' => $attribute->description,
            'type' => $attribute->type->value,
            'global' => $attribute->global,
            'sortable' => $attribute->sortable,
        ];

        // Expected full response
        $this->expected = array_merge($this->expected_short, $expected_attribute, [
            'description_html' => $this->product->description_html,
            'description_short' => $this->product->description_short,
            'gallery' => [],
            'schemas' => [
                [
                    'name' => 'Rozmiar',
                    'type' => 'select',
                    'required' => true,
                    'available' => true,
                    //'prices' => [['value' => 0, 'currency' => $this->currency->value]],
                    'metadata' => [],
                    'options' => [
                        [
                            'name' => 'XL',
                            //'prices' => [['value' => 0, 'currency' => $this->currency->value]],
                            'disabled' => false,
                            'available' => true,
                            'items' => [[
                                'name' => 'Koszulka XL',
                                'sku' => 'K001/XL',
                            ]],
                            'metadata' => [],
                        ],
                        [
                            'name' => 'L',
                            //'prices' => [['value' => 0, 'currency' => $this->currency->value]],
                            'disabled' => false,
                            'available' => false,
                            'items' => [[
                                'name' => 'Koszulka L',
                                'sku' => 'K001/L',
                            ]],
                            'metadata' => [],
                        ],
                    ],
                ],
            ],
            'metadata' => [
                $metadata->name => $metadata->value,
            ],
        ]);

        $this->saleProduct = Product::factory()->create([
            'public' => true,
        ]);
        $this->productRepository->setProductPrices($this->saleProduct->getKey(), [
            ProductPriceType::PRICE_BASE->value => [PriceDto::from(Money::of(3000, $this->currency->value))],
            ProductPriceType::PRICE_MIN_INITIAL->value => [PriceDto::from(Money::of(2500, $this->currency->value))],
            ProductPriceType::PRICE_MAX_INITIAL->value => [PriceDto::from(Money::of(3500, $this->currency->value))],
        ]);
    }

    /**
     * @dataProvider authProvider
     */
    public function testIndexWithTranslationsFlag(string $user): void
    {
        $this->{$user}->givePermissionTo('products.show');

        $response = $this->actingAs($this->{$user})->getJson('/products?limit=100&with_translations=1');
        $response
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJson(['data' => [
                $this->expected_short,
            ]])
            ->assertJsonFragment([
                [
                    'net' => '100.00',
                    'gross' => '100.00',
                    'currency' => 'PLN',
                ],
            ]);

        $this->assertArrayHasKey('translations', $response->json('data.0'));
        $this->assertIsArray($response->json('data.0.translations'));
        $this->assertQueryCountLessThan(24);
    }

    public function testIndexUnauthorized(): void
    {
        $this->getJson('/products')->assertForbidden();
    }

    /**
     * @dataProvider authProvider
     */
    public function testIndex(string $user): void
    {
        $this->{$user}->givePermissionTo('products.show');

        $product = Product::factory()->create([
            'public' => true,
        ]);
        $set = ProductSet::factory()->create([
            'public' => true,
        ]);
        $product->sets()->sync([$set->getKey()]);

        $this
            ->actingAs($this->{$user})
            ->json('GET', '/products', ['limit' => 100])
            ->assertOk()
            ->assertJsonCount(3, 'data')
            ->assertJson([
                'data' => [
                    0 => $this->expected_short,
                ],
            ])
            ->assertJsonFragment([
                [
                    'net' => '100.00',
                    'gross' => '100.00',
                    'currency' => 'PLN',
                ],
            ]);

        $this->assertQueryCountLessThan(29);
    }

    /**
     * @dataProvider authProvider
     */
    public function testShowWithTranslationsFlagHidden(string $user): void
    {
        $this->{$user}->givePermissionTo(['products.show_details', 'products.show_hidden']);

        /** @var Product $product */
        $product = Product::factory()->create([
            'name' => 'Test product with translations',
            'public' => true,
        ]);

        /** @var Language $language */
        $language = Language::query()->create([
            'iso' => 'fr',
            'name' => 'France',
            'default' => false,
            'hidden' => true,
        ]);

        $product->setLocale($language->getKey())->update([
            'name' => 'Test FR translation',
        ]);

        $response = $this
            ->actingAs($this->{$user})
            ->json('GET', "/products/{$product->slug}?with_translations=1");

        $response->assertOk();

        $this->arrayHasKey('translations', $response->json('data'));
        $this->arrayHasKey($language->getKey(), $response->json('data.translations'));
    }

    /**
     * @dataProvider authProvider
     */
    public function testIndexHidden(string $user): void
    {
        $this->{$user}->givePermissionTo(['products.show', 'products.show_hidden']);

        $product = Product::factory()->create([
            'public' => true,
        ]);
        $set = ProductSet::factory()->create([
            'public' => false,
        ]);

        $product->sets()->sync([$set->getKey()]);

        $this->actingAs($this->{$user})
            ->json('GET', '/products')
            ->assertOk()
            ->assertJsonCount(4, 'data'); // Should show all products.
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
    public function testShow(string $user): void
    {
        $this->{$user}->givePermissionTo('products.show_details');

        $this
            ->actingAs($this->{$user})
            ->getJson('/products/' . $this->product->slug)
            ->assertOk()
            ->assertJson(['data' => $this->expected]);

        $this
            ->actingAs($this->{$user})
            ->getJson('/products/id:' . $this->product->getKey())
            ->assertOk()
            ->assertJson(['data' => $this->expected]);
    }

    /**
     * @dataProvider authProvider
     */
    public function testShowWithAttributeMetadata(string $user): void
    {
        $this->{$user}->givePermissionTo('products.show_details');

        $this->product->attributes->first()->metadata()->create([
            'name' => 'testMeta',
            'value' => 'testValue',
            'value_type' => MetadataType::STRING,
            'public' => true,
        ]);

        $this
            ->actingAs($this->{$user})
            ->getJson('/products/' . $this->product->slug)
            ->assertOk()
            ->assertJson(['data' => $this->expected])
            ->assertJsonFragment([
                'metadata' => [
                    'testMeta' => 'testValue',
                ],
            ]);
    }

    /**
     * @dataProvider authProvider
     */
    public function testShowWrongIdOrSlug(string $user): void
    {
        $this->{$user}->givePermissionTo('products.show_details');

        $this->actingAs($this->{$user})
            ->getJson('/products/its_wrong_slug')
            ->assertNotFound();

        $this->actingAs($this->{$user})
            ->getJson('/products/id:its-not-uuid')
            ->assertNotFound();

        $this->actingAs($this->{$user})
            ->getJson('/products/id:' . $this->product->getKey() . $this->product->getKey())
            ->assertNotFound();
    }

    /**
     * @dataProvider authProvider
     */
    public function testShowSets(string $user): void
    {
        $this->{$user}->givePermissionTo('products.show_details');

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
            ->actingAs($this->{$user})
            ->getJson('/products/' . $product->slug)
            ->assertOk()
            ->assertJsonFragment([
                'sets' => [
                    [
                        'id' => $set1->getKey(),
                        'name' => $set1->name,
                        'slug' => $set1->slug,
                        'slug_suffix' => $set1->slugSuffix,
                        'slug_override' => $set1->slugOverride,
                        'public' => $set1->public,
                        'visible' => $set1->public_parent && $set1->public,
                        'parent_id' => $set1->parent_id,
                        'children_ids' => [],
                        'cover' => null,
                        'metadata' => [],
                        'published' => [
                            $this->lang,
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
                        'parent_id' => $set2->parent_id,
                        'children_ids' => [],
                        'cover' => null,
                        'metadata' => [],
                        'published' => [
                            $this->lang,
                        ],
                    ],
                ],
            ]);
    }

    /**
     * @dataProvider authProvider
     */
    public function testShowPrivateSetsNoPermission(string $user): void
    {
        $this->{$user}->givePermissionTo('products.show_details');

        $product = Product::factory()->create([
            'public' => true,
        ]);

        $set1 = ProductSet::factory()->create([
            'public' => true,
        ]);
        $set2 = ProductSet::factory()->create([
            'public' => false,
        ]);

        $product->sets()->sync([$set1->getKey(), $set2->getKey()]);

        $this
            ->actingAs($this->{$user})
            ->getJson('/products/' . $product->slug)
            ->assertOk()
            ->assertJsonFragment([
                'sets' => [
                    [
                        'id' => $set1->getKey(),
                        'name' => $set1->name,
                        'slug' => $set1->slug,
                        'slug_suffix' => $set1->slugSuffix,
                        'slug_override' => $set1->slugOverride,
                        'public' => $set1->public,
                        'visible' => $set1->public_parent && $set1->public,
                        'parent_id' => $set1->parent_id,
                        'children_ids' => [],
                        'cover' => null,
                        'metadata' => [],
                        'published' => [
                            $this->lang,
                        ],
                    ],
                ],
            ]);
    }

    /**
     * @dataProvider authProvider
     */
    public function testShowPrivateSetsWithPermission(string $user): void
    {
        $this->{$user}->givePermissionTo(['products.show_details', 'product_sets.show_hidden']);

        $product = Product::factory()->create([
            'public' => true,
        ]);

        $set1 = ProductSet::factory()->create([
            'public' => true,
        ]);
        $set2 = ProductSet::factory()->create([
            'public' => false,
        ]);

        $product->sets()->sync([$set1->getKey(), $set2->getKey()]);

        $this
            ->actingAs($this->{$user})
            ->getJson('/products/' . $product->slug)
            ->assertOk()
            ->assertJsonFragment([
                'sets' => [
                    [
                        'id' => $set1->getKey(),
                        'name' => $set1->name,
                        'slug' => $set1->slug,
                        'slug_suffix' => $set1->slugSuffix,
                        'slug_override' => $set1->slugOverride,
                        'public' => $set1->public,
                        'visible' => $set1->public_parent && $set1->public,
                        'parent_id' => $set1->parent_id,
                        'children_ids' => [],
                        'cover' => null,
                        'metadata' => [],
                        'published' => [
                            $this->lang,
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
                        'parent_id' => $set2->parent_id,
                        'children_ids' => [],
                        'cover' => null,
                        'metadata' => [],
                        'published' => [
                            $this->lang,
                        ],
                    ],
                ],
            ]);
    }

    /**
     * @dataProvider authProvider
     */
    public function testShowSetsWithCover(string $user): void
    {
        $this->{$user}->givePermissionTo('products.show_details');

        $product = Product::factory()->create([
            'public' => true,
        ]);

        $media1 = Media::factory()->create([
            'type' => MediaType::PHOTO,
            'url' => 'https://picsum.photos/seed/' . mt_rand(0, 999999) . '/800',
        ]);

        $media2 = Media::factory()->create([
            'type' => MediaType::PHOTO,
            'url' => 'https://picsum.photos/seed/' . mt_rand(0, 999999) . '/800',
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

        $response = $this->actingAs($this->{$user})
            ->getJson('/products/' . $product->slug);
        $response
            ->assertOk()
            ->assertJsonFragment([
                'sets' => [
                    [
                        'id' => $set1->getKey(),
                        'name' => $set1->name,
                        'slug' => $set1->slug,
                        'slug_suffix' => $set1->slugSuffix,
                        'slug_override' => $set1->slugOverride,
                        'public' => $set1->public,
                        'visible' => $set1->public_parent && $set1->public,
                        'parent_id' => $set1->parent_id,
                        'children_ids' => [],
                        'metadata' => [],
                        'cover' => [
                            'id' => $media1->getKey(),
                            'type' => $media1->type->value,
                            'url' => $media1->url,
                            'slug' => $media1->slug,
                            'alt' => $media1->alt,
                            'source' => $media1->source->value,
                            'metadata' => [],
                        ],
                        'published' => [
                            $this->lang,
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
                        'parent_id' => $set2->parent_id,
                        'children_ids' => [],
                        'metadata' => [],
                        'cover' => [
                            'id' => $media2->getKey(),
                            'type' => $media2->type->value,
                            'url' => $media2->url,
                            'slug' => $media2->slug,
                            'alt' => $media2->alt,
                            'source' => $media2->source->value,
                            'metadata' => [],
                        ],
                        'published' => [
                            $this->lang,
                        ],
                    ],
                ],
            ]);
    }

    /**
     * @dataProvider authProvider
     */
    public function testShowAttributes(string $user): void
    {
        $this->{$user}->givePermissionTo('products.show_details');

        $product = Product::factory()->create([
            'public' => true,
        ]);

        $attribute = Attribute::factory()->create();

        $option = AttributeOption::factory()->create([
            'index' => 1,
            'attribute_id' => $attribute->getKey(),
        ]);

        $product->attributes()->attach($attribute->getKey());

        $product->attributes->first()->pivot->options()->attach($option->getKey());

        $this
            ->actingAs($this->{$user})
            ->getJson('/products/' . $product->slug)
            ->assertOk()
            ->assertJsonFragment([
                'id' => $attribute->getKey(),
                'name' => $attribute->name,
                'description' => $attribute->description,
                'type' => $attribute->type,
                'global' => $attribute->global,
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
    public function testShowPrivateMetadata(string $user): void
    {
        $this->{$user}->givePermissionTo(['products.show_details', 'products.show_metadata_private']);

        $privateMetadata = $this->product->metadataPrivate()->create([
            'name' => 'hiddenMetadata',
            'value' => 'hidden metadata test',
            'value_type' => MetadataType::STRING,
            'public' => false,
        ]);

        $response = $this->actingAs($this->{$user})
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
    public function testShowHiddenUnauthorized(string $user): void
    {
        $this->{$user}->givePermissionTo('products.show_details');

        $response = $this->actingAs($this->{$user})
            ->getJson('/products/' . $this->hidden_product->slug);
        $response->assertNotFound();

        $response = $this->actingAs($this->{$user})
            ->getJson('/products/id:' . $this->hidden_product->getKey());
        $response->assertNotFound();
    }

    /**
     * Sets shouldn't affect product visibility.
     *
     * @dataProvider authProvider
     */
    public function testShowHiddenWithPublicSetUnauthorized(string $user): void
    {
        $this->{$user}->givePermissionTo('products.show_details');

        /** @var ProductSet $publicSet */
        $publicSet = ProductSet::factory()->create([
            'public' => true,
        ]);

        $publicSet->products()->attach($this->hidden_product);

        $response = $this->actingAs($this->{$user})
            ->getJson('/products/' . $this->hidden_product->slug);
        $response->assertNotFound();

        $response = $this->actingAs($this->{$user})
            ->getJson('/products/id:' . $this->hidden_product->getKey());
        $response->assertNotFound();
    }

    /**
     * @dataProvider authProvider
     */
    public function testShowHidden(string $user): void
    {
        $this->{$user}->givePermissionTo(['products.show_details', 'products.show_hidden']);

        $response = $this->actingAs($this->{$user})
            ->getJson('/products/' . $this->hidden_product->slug);
        $response->assertOk();

        $response = $this->actingAs($this->{$user})
            ->getJson('/products/id:' . $this->hidden_product->getKey());
        $response->assertOk();
    }

    public static function noIndexProvider(): array
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
    public function testShowSeoNoIndex(string $user, bool $noIndex): void
    {
        $this->{$user}->givePermissionTo('products.show_details');

        $product = Product::factory([
            'public' => true,
        ])->create();

        $seo = SeoMetadata::factory([
            'no_index' => $noIndex,
            'header_tags' => ['test1', 'test2'],
        ])->create();

        $product->seo()->save($seo);

        $response = $this->actingAs($this->{$user})
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
                'header_tags' => ['test1', 'test2'],
                'published' => [$this->lang],
            ]]);
    }

    /**
     * @dataProvider authProvider
     */
    public function testShowWithSales(string $user): void
    {
        $this->markTestSkipped();

        $this->{$user}->givePermissionTo('products.show_details');

        // Applied - product is on list
        $sale1 = Discount::factory()->create([
            'description' => 'Testowa promocja',
            'name' => 'Testowa promocja obowiązująca',
            'percentage' => '10',
            'target_type' => DiscountTargetType::PRODUCTS,
            'target_is_allow_list' => true,
            'code' => null,
        ]);

        $sale1->products()->attach($this->saleProduct);

        // Not applied - product is not on list
        $sale2 = Discount::factory()->create([
            'description' => 'Testowa promocja',
            'name' => 'Testowa promocja',
            'value' => 10,
            'type' => DiscountType::AMOUNT,
            'target_type' => DiscountTargetType::PRODUCTS,
            'target_is_allow_list' => true,
            'code' => null,
        ]);

        // Not applied - invalid target type
        $sale3 = Discount::factory()->create([
            'description' => 'Testowa promocja',
            'name' => 'Order-value',
            'percentage' => '5',
            'target_type' => DiscountTargetType::ORDER_VALUE,
            'target_is_allow_list' => true,
            'code' => null,
        ]);

        $sale3->products()->attach($this->saleProduct);

        // Not applied - product is on list, but target_is_allow_list = false
        $sale4 = Discount::factory()->create([
            'description' => 'Testowa promocja',
            'name' => 'Not allow list',
            'percentage' => '5',
            'target_type' => DiscountTargetType::PRODUCTS,
            'target_is_allow_list' => false,
            'code' => null,
        ]);

        $sale4->products()->attach($this->saleProduct);

        // Not applied - invalid condition type in condition group
        $sale5 = Discount::factory()->create([
            'description' => 'Testowa promocja',
            'name' => 'Condition type Order-value',
            'percentage' => '5',
            'target_type' => DiscountTargetType::PRODUCTS,
            'target_is_allow_list' => true,
            'code' => null,
        ]);

        $conditionGroup = ConditionGroup::create();

        $conditionGroup->conditions()->create([
            'condition_group_id' => $conditionGroup->getKey(),
            'type' => ConditionType::ORDER_VALUE,
            'value' => [
                'min_value' => 20,
                'max_value' => 10000,
                'include_taxes' => false,
                'is_in_range' => true,
            ],
        ]);

        $sale5->conditionGroups()->attach($conditionGroup);

        $sale5->products()->attach($this->saleProduct);

        $this->discountService->applyDiscountsOnProduct($this->saleProduct);

        $response = $this->actingAs($this->{$user})
            ->getJson('/products/id:' . $this->saleProduct->getKey());

        $response
            ->assertOk()
            ->assertJsonFragment([
                'id' => $this->saleProduct->getKey(),
                'name' => $this->saleProduct->name,
                'prices_base' => [[
                    'gross' => '3000.00',
                    'currency' => Currency::DEFAULT->value,
                ]],
                'prices_min_initial' => [[
                    'gross' => '2500.00',
                    'currency' => Currency::DEFAULT->value,
                ]],
                'prices_max_initial' => [[
                    'gross' => '3500.00',
                    'currency' => Currency::DEFAULT->value,
                ]],
                'prices_min' => [[
                    'gross' => '2250.00',
                    'currency' => Currency::DEFAULT->value,
                ]],
                'prices_max' => [[
                    'gross' => '3150.00',
                    'currency' => Currency::DEFAULT->value,
                ]],
            ])
            ->assertJsonFragment([
                'id' => $sale1->getKey(),
                'name' => $sale1->name,
            ])
            ->assertJsonMissing([
                'id' => $sale2->getKey(),
            ])
            ->assertJsonMissing([
                'id' => $sale3->getKey(),
            ])
            ->assertJsonMissing([
                'id' => $sale4->getKey(),
            ])
            ->assertJsonMissing([
                'id' => $sale5->getKey(),
            ]);
    }

    /**
     * @dataProvider authProvider
     */
    public function testShowWithSalesBlockListEmpty(string $user): void
    {
        $this->{$user}->givePermissionTo('products.show_details');

        // Applied - product is not on block list
        $sale = Discount::factory()->create([
            'description' => 'Testowa promocja',
            'name' => 'Testowa promocja obowiązująca',
            'percentage' => '10',
            'target_type' => DiscountTargetType::PRODUCTS,
            'target_is_allow_list' => false,
            'code' => null,
        ]);

        $this->discountService->applyDiscountsOnProduct($this->saleProduct);

        $response = $this->actingAs($this->{$user})
            ->getJson('/products/id:' . $this->saleProduct->getKey());

        $response
            ->assertOk()
            ->assertJsonFragment([
                'id' => $this->saleProduct->getKey(),
                'name' => $this->saleProduct->name,
                'prices_base' => [[
                    'net' => '3000.00',
                    'gross' => '3000.00',
                    'currency' => Currency::DEFAULT->value,
                ]],
                'prices_min_initial' => [[
                    'net' => '2500.00',
                    'gross' => '2500.00',
                    'currency' => Currency::DEFAULT->value,
                ]],
                'prices_max_initial' => [[
                    'net' => '3500.00',
                    'gross' => '3500.00',
                    'currency' => Currency::DEFAULT->value,
                ]],
                'prices_min' => [[
                    'net' => '2250.00',
                    'gross' => '2250.00',
                    'currency' => Currency::DEFAULT->value,
                ]],
                'prices_max' => [[
                    'net' => '3150.00',
                    'gross' => '3150.00',
                    'currency' => Currency::DEFAULT->value,
                ]],
            ])
            ->assertJsonFragment([
                'id' => $sale->getKey(),
                'name' => $sale->name,
            ]);
    }

    /**
     * @dataProvider authProvider
     */
    public function testShowWithSalesProductSets(string $user): void
    {
        $this->{$user}->givePermissionTo('products.show_details');

        $set = ProductSet::factory()->create([
            'public' => true,
            'order' => 20,
        ]);

        $this->saleProduct->sets()->sync([$set->getKey()]);

        // Applied - product set is on allow list
        $sale1 = Discount::factory()->create([
            'description' => 'Testowa promocja',
            'name' => 'Testowa promocja obowiązująca',
            'percentage' => '10',
            'target_type' => DiscountTargetType::PRODUCTS,
            'target_is_allow_list' => true,
            'code' => null,
            'priority' => 1,
        ]);

        $sale1->productSets()->attach($set);

        // Not applied - product set is on block list
        $sale2 = Discount::factory()->create([
            'description' => 'Not applied - product set is on block list',
            'name' => 'Set on block list',
            'percentage' => '5',
            'target_type' => DiscountTargetType::PRODUCTS,
            'target_is_allow_list' => false,
            'code' => null,
        ]);

        $sale2->productSets()->attach($set);

        // Not applied - product set is not on list
        $sale3 = Discount::factory()->create([
            'description' => 'Not applied - product set is not on list',
            'name' => 'Set not on list',
            'percentage' => '5',
            'target_type' => DiscountTargetType::PRODUCTS,
            'target_is_allow_list' => true,
            'code' => null,
        ]);

        // Applied - product set is not on block list
        $sale4 = Discount::factory()->create([
            'description' => 'Not applied - product set is on block list',
            'name' => 'Set not on block list',
            'percentage' => '5',
            'target_type' => DiscountTargetType::PRODUCTS,
            'target_is_allow_list' => false,
            'code' => null,
            'priority' => 0,
        ]);

        $this->discountService->applyDiscountsOnProduct($this->saleProduct);

        $response = $this->actingAs($this->{$user})
            ->getJson('/products/id:' . $this->saleProduct->getKey());

        $response
            ->assertOk()
            ->assertJsonFragment([
                'id' => $this->saleProduct->getKey(),
                'name' => $this->saleProduct->name,
                'prices_base' => [[
                    'net' => '3000.00',
                    'gross' => '3000.00',
                    'currency' => Currency::DEFAULT->value,
                ]],
                'prices_min_initial' => [[
                    'net' => '2500.00',
                    'gross' => '2500.00',
                    'currency' => Currency::DEFAULT->value,
                ]],
                'prices_max_initial' => [[
                    'net' => '3500.00',
                    'gross' => '3500.00',
                    'currency' => Currency::DEFAULT->value,
                ]],
                'prices_min' => [[
                    'net' => '2137.50',
                    'gross' => '2137.50',
                    'currency' => Currency::DEFAULT->value,
                ]],
                'prices_max' => [[
                    'net' => '2992.50',
                    'gross' => '2992.50',
                    'currency' => Currency::DEFAULT->value,
                ]],
            ])
            ->assertJsonFragment([
                'id' => $sale1->getKey(),
                'name' => $sale1->name,
            ])
            ->assertJsonFragment([
                'id' => $sale4->getKey(),
                'name' => $sale4->name,
            ])
            ->assertJsonMissing([
                'id' => $sale2->getKey(),
                'name' => $sale2->name,
            ])
            ->assertJsonMissing([
                'id' => $sale3->getKey(),
                'name' => $sale3->name,
            ]);
    }

    /**
     * @dataProvider authProvider
     */
    public function testShowWithSalesProductSetsChildren(string $user): void
    {
        $this->{$user}->givePermissionTo('products.show_details');

        $parentSet = ProductSet::factory()->create([
            'public' => true,
            'name' => 'parent',
        ]);

        $childrenSet = ProductSet::factory()->create([
            'public' => true,
            'name' => 'children',
            'public_parent' => true,
            'parent_id' => $parentSet->getKey(),
        ]);

        $subChildrenSet = ProductSet::factory()->create([
            'public' => true,
            'name' => 'sub children',
            'public_parent' => true,
            'parent_id' => $childrenSet->getKey(),
        ]);

        $this->saleProduct->sets()->sync([$subChildrenSet->getKey()]);

        // Applied - product set is on allow list
        $sale1 = Discount::factory()->create([
            'description' => 'Testowa promocja',
            'name' => 'Testowa promocja obowiązująca',
            'percentage' => '10',
            'target_type' => DiscountTargetType::PRODUCTS,
            'target_is_allow_list' => true,
            'code' => null,
            'priority' => 1,
        ]);

        $sale1->productSets()->attach($parentSet);

        // Not applied - product set is on block list
        $sale2 = Discount::factory()->create([
            'description' => 'Not applied - product set is on block list',
            'name' => 'Set on block list',
            'percentage' => '5',
            'target_type' => DiscountTargetType::PRODUCTS,
            'target_is_allow_list' => false,
            'code' => null,
        ]);

        $sale2->productSets()->attach($parentSet);

        $this->discountService->applyDiscountsOnProduct($this->saleProduct);

        $response = $this->actingAs($this->{$user})
            ->getJson('/products/id:' . $this->saleProduct->getKey());

        $response
            ->assertOk()
            ->assertJsonFragment([
                'id' => $this->saleProduct->getKey(),
                'name' => $this->saleProduct->name,
                'prices_base' => [[
                    'net' => '3000.00',
                    'gross' => '3000.00',
                    'currency' => Currency::DEFAULT->value,
                ]],
                'prices_min_initial' => [[
                    'net' => '2500.00',
                    'gross' => '2500.00',
                    'currency' => Currency::DEFAULT->value,
                ]],
                'prices_max_initial' => [[
                    'net' => '3500.00',
                    'gross' => '3500.00',
                    'currency' => Currency::DEFAULT->value,
                ]],
                'prices_min' => [[
                    'net' => '2250.00',
                    'gross' => '2250.00',
                    'currency' => Currency::DEFAULT->value,
                ]],
                'prices_max' => [[
                    'net' => '3150.00',
                    'gross' => '3150.00',
                    'currency' => Currency::DEFAULT->value,
                ]],
            ])
            ->assertJsonFragment([
                'id' => $sale1->getKey(),
                'name' => $sale1->name,
            ])
            ->assertJsonMissing([
                'id' => $sale2->getKey(),
                'name' => $sale2->name,
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
            ->assertJson(['data' => [
                'slug' => 'test',
                'name' => 'Test',
                'public' => true,
                'shipping_digital' => false,
                'description_html' => '<h1>Description</h1>',
                'description_short' => 'So called short description...',
                'cover' => null,
                'gallery' => [],
            ]])
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

        return [$product, $productPricesMin, $productPricesMax, new ProductPriceUpdated($product->getKey(), null, null, $productPricesMin->toArray(), $productPricesMax->toArray())];
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

        [$product, $productPricesMin, $productPricesMax, $event] = $payload;

        $listener = new WebHookEventListener();

        $listener->handle($event);

        Bus::assertDispatched(CallWebhookJob::class, function ($job) use ($webHook, $product, $productPricesMin, $productPricesMax) {
            $payload = $job->payload;

            return $job->webhookUrl === $webHook->url
                && isset($job->headers['Signature'])
                && $payload['data']['id'] === $product->getKey()
                && $payload['data']['prices'][0]['currency'] === Currency::DEFAULT->value
                && $payload['data']['prices'][0]['old_price_min'] === null
                && $payload['data']['prices'][0]['old_price_max'] === null
                && $payload['data']['prices'][0]['new_price_min'] === $productPricesMin
                    ->where(fn (PriceDto $dto) => $dto->currency === Currency::DEFAULT)->first()->value->getAmount()
                && $payload['data']['prices'][0]['new_price_max'] === $productPricesMax
                    ->where(fn (PriceDto $dto) => $dto->currency === Currency::DEFAULT)->first()->value->getAmount()
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
            ->assertJson(['data' => [
                'slug' => 'test',
                'name' => 'Test',
                'public' => true,
                'shipping_digital' => false,
                'description_html' => '<h1>Description</h1>',
                'cover' => null,
                'gallery' => [],
            ]]);

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
            ->assertJson(['data' => [
                'slug' => 'test',
                'name' => 'Test',
                'public' => true,
                'shipping_digital' => false,
                'description_html' => '<h1>Description</h1>',
                'cover' => null,
                'gallery' => [],
            ]]);

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
            ->assertJson(['data' => [
                'slug' => 'test',
                'name' => 'Test',
                'public' => false,
                'shipping_digital' => false,
                'description_html' => '<h1>Description</h1>',
                'cover' => null,
                'gallery' => [],
            ]]);

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
            ->assertJson(['data' => [
                'id' => $uuid,
                'slug' => 'test',
                'name' => 'Test',
                'public' => true,
                'shipping_digital' => false,
            ]]);

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

        Event::assertDispatched(ProductCreated::class);
    }

    /**
     * @dataProvider booleanProvider
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

        $response->assertCreated();

        $product = Product::query()->find($response->json('data.id'))->first();

        $this->assertDatabaseHas('seo_metadata', [
            "title->{$this->lang}" => 'seo title',
            "description->{$this->lang}" => 'seo description',
            'model_id' => $product->getKey(),
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

        $schema = $this->schemaCrudService->store(FakeDto::schemaDto([
            'type' => SchemaType::STRING,
            'required' => false,
            'prices' => [['value' => $schemaPrice, 'currency' => Currency::DEFAULT->value]],
        ]));

        $response = $this->actingAs($this->{$user})->postJson('/products', FakeDto::productCreateData([
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
        ]));

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
        $schema = $this->schemaCrudService->store(FakeDto::schemaDto([
            'type' => SchemaType::STRING,
            'required' => true,
            'prices' => [['value' => $schemaPrice, 'currency' => Currency::DEFAULT->value]],
        ]));

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

        $this->assertDatabaseCount('product_attribute_attribute_option', 3); // +1 from $this->product
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

        $this->assertDatabaseCount('product_attribute_attribute_option', 3); // +1 from $this->product
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
        $this->markTestSkipped();

        $this->{$user}->givePermissionTo('products.add');

        $saleNotApplied = Discount::factory()->create([
            'type' => DiscountType::AMOUNT,
            'value' => 10,
            'target_type' => DiscountTargetType::PRODUCTS,
            'target_is_allow_list' => true,
            'code' => null,
        ]);

        $saleApplied = Discount::factory()->create([
            'type' => DiscountType::AMOUNT,
            'value' => 20,
            'target_type' => DiscountTargetType::PRODUCTS,
            'target_is_allow_list' => false,
            'code' => null,
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
            'translations' => [$this->lang => [
                'name' => 'Updated',
                'description_html' => '<h1>New description</h1>',
                'description_short' => 'New so called short description',
            ]],
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
    public function testUpdateWithSeo(string $user): void
    {
        $this->{$user}->givePermissionTo('products.edit');

        $this->product->update([
            'name' => 'Created',
            'slug' => 'created',
            'description_html' => '<h1>Description</h1>',
            'public' => false,
            'order' => 1,
        ]);

        $seo = SeoMetadata::factory()->create();
        $this->product->seo()->save($seo);

        $this->actingAs($this->{$user})->json('PATCH', '/products/id:' . $this->product->getKey(), [
            'seo' => [
                'translations' => [
                    $this->lang => [
                        'title' => 'seo title',
                        'description' => 'seo description',
                    ],
                ],
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
    public function testUpdateMinMaxPrice(string $user): void
    {
        $this->{$user}->givePermissionTo('products.edit');

        $this->product->schemas()->detach();

        $schemaPrice = 50;
        $schema = $this->schemaCrudService->store(FakeDto::schemaDto([
            'type' => 0,
            'required' => false,
            'prices' => [['value' => $schemaPrice, 'currency' => Currency::DEFAULT->value]],
        ]));

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
        $this->markTestSkipped();

        $this->{$user}->givePermissionTo('products.edit');

        $schemaPrice = 50;
        $schema = $this->schemaCrudService->store(FakeDto::schemaDto([
            'type' => 0,
            'required' => false,
            'prices' => [['value' => $schemaPrice, 'currency' => Currency::DEFAULT->value]],
        ]));

        $this->product->schemas()->attach($schema->getKey());
        $this->productService->updateMinMaxPrices($this->product);

        $saleValue = 25;
        $sale = Discount::factory()->create([
            'code' => null,
            'type' => DiscountType::AMOUNT,
            'value' => $saleValue,
            'target_type' => DiscountTargetType::PRODUCTS,
            'target_is_allow_list' => true,
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
        $schema = $this->schemaCrudService->store(FakeDto::schemaDto([
            'type' => 0,
            'required' => true,
            'prices' => [['value' => $schemaPrice, 'currency' => Currency::DEFAULT->value]],
        ]));

        $this->product->schemas()->attach($schema->getKey());
        $this->productService->updateMinMaxPrices($this->product);

        $schemaNewPrice = 75;
        $response = $this->actingAs($this->{$user})->patchJson('/schemas/id:' . $schema->getKey(), FakeDto::schemaData([
            'name' => 'Test Updated',
            'prices' => [['value' => $schemaNewPrice, 'currency' => Currency::DEFAULT->value]],
            'type' => 'string',
            'required' => false,
        ]));

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
    public function testDeleteSchemaMinMaxPrice(string $user): void
    {
        $this->{$user}->givePermissionTo('schemas.remove');

        $schemaPrice = 50;
        $schema = $this->schemaCrudService->store(FakeDto::schemaDto([
            'type' => 0,
            'required' => true,
            'prices' => [['value' => $schemaPrice, 'currency' => Currency::DEFAULT->value]],
        ]));

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
            'order' => 1,
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
            'order' => 1,
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

    /**
     * @dataProvider authProvider
     */
    public function testProductHasSchemaOnSchemaDelete(string $user): void
    {
        $this->{$user}->givePermissionTo('schemas.remove');

        Schema::query()->delete();
        $schema = $this->schemaCrudService->store(FakeDto::schemaDto([
            'name' => 'test schema',
        ]));

        $this->product->schemas()->save($schema);
        $this->product->update(['has_schemas' => true]);

        $this->actingAs($this->{$user})->json('delete', 'schemas/id:' . $schema->getKey());

        $this->assertDatabaseHas('products', [
            'id' => $this->product->getKey(),
            'has_schemas' => false,
        ]);
    }

    /**
     * @dataProvider authProvider
     */
    public function testProductHasSchemaOnSchemaAdded(string $user): void
    {
        $this->{$user}->givePermissionTo('products.edit');

        Schema::query()->delete();
        $schema = $this->schemaCrudService->store(FakeDto::schemaDto([
            'name' => 'test schema',
        ]));

        $this->actingAs($this->{$user})->json('patch', 'products/id:' . $this->product->getKey(), [
            'schemas' => [
                $schema->getKey(),
            ],
        ]);

        $this->assertDatabaseHas('products', [
            'id' => $this->product->getKey(),
            'has_schemas' => true,
        ]);
    }

    /**
     * @dataProvider authProvider
     */
    public function testProductHasSchemaOnSchemasRemovedFromProduct(string $user): void
    {
        $this->{$user}->givePermissionTo('products.edit');

        Schema::query()->delete();
        $schema = $this->schemaCrudService->store(FakeDto::schemaDto([
            'name' => 'test schema',
        ]));

        $this->product->schemas()->save($schema);

        $this->product->update(['has_schemas' => true]);

        $this->actingAs($this->{$user})->json('patch', 'products/id:' . $this->product->getKey(), [
            'schemas' => [],
        ]);

        $this->assertDatabaseHas('products', [
            'id' => $this->product->getKey(),
            'has_schemas' => false,
        ]);
    }
}
