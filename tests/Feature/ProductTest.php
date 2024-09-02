<?php

namespace Tests\Feature;

use App\Enums\ConditionType;
use App\Enums\DiscountTargetType;
use App\Enums\ExceptionsEnums\Exceptions;
use App\Enums\MediaType;
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
use App\Models\WebHook;
use App\Repositories\DiscountRepository;
use App\Services\Contracts\AvailabilityServiceContract;
use App\Services\DiscountService;
use App\Services\ProductService;
use Brick\Math\Exception\NumberFormatException;
use Brick\Math\Exception\RoundingNecessaryException;
use Brick\Money\Exception\UnknownCurrencyException;
use Brick\Money\Money;
use Domain\Currency\Currency;
use Domain\Language\Language;
use Domain\Metadata\Enums\MetadataType;
use Domain\Price\Dtos\PriceDto;
use Domain\Price\Enums\DiscountConditionPriceType;
use Domain\Price\Enums\ProductPriceType;
use Domain\Price\PriceService;
use Domain\PriceMap\PriceMapService;
use Domain\ProductAttribute\Enums\AttributeType;
use Domain\ProductAttribute\Models\Attribute;
use Domain\ProductAttribute\Models\AttributeOption;
use Domain\ProductSchema\Models\Schema;
use Domain\ProductSchema\Services\SchemaCrudService;
use Domain\ProductSet\ProductSet;
use Domain\SalesChannel\Models\SalesChannel;
use Domain\SalesChannel\SalesChannelRepository;
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

    private DiscountRepository $discountRepository;
    private DiscountService $discountService;
    private PriceService $priceService;
    private ProductService $productService;
    private SchemaCrudService $schemaCrudService;
    private SalesChannel $salesChannel;
    private PriceMapService $priceMapService;

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
        $this->discountService = App::make(DiscountService::class);
        $this->schemaCrudService = App::make(SchemaCrudService::class);
        $this->discountRepository = App::make(DiscountRepository::class);
        $this->priceService = App::make(PriceService::class);
        $this->salesChannel = App::make(SalesChannelRepository::class)->getDefault();
        $this->priceMapService = App::make(PriceMapService::class);

        $this->productPrices = array_map(fn(Currency $currency) => [
            'value' => '100.00',
            'currency' => $currency->value,
        ], Currency::cases());

        /** @var AvailabilityServiceContract $availabilityService */
        $availabilityService = App::make(AvailabilityServiceContract::class);

        $this->product = $this->productService->create(
            FakeDto::productCreateDto([
                'shipping_digital' => false,
                'public' => true,
                'created_at' => now()->subHours(5),
                'prices_base' => $this->productPrices,
            ])
        );

        $schema = $this->schemaCrudService->store(
            FakeDto::schemaDto([
                'name' => 'Rozmiar',
                'required' => true,
                'product_id' => $this->product->getKey(),
            ], false, false)
        );

        $this->travel(5)->hours();

        $l = $schema->options()->create([
            'name' => 'L',
            'order' => 2,
        ]);
        $this->priceMapService->updateOptionPricesForDefaultMaps($l, FakeDto::generatePricesInAllCurrencies([],0));

        $l->items()->create([
            'name' => 'Koszulka L',
            'sku' => 'K001/L',
        ]);

        $this->travelBack();

        $xl = $schema->options()->create([
            'name' => 'XL',
            'order' => 1,
        ]);
        $this->priceMapService->updateOptionPricesForDefaultMaps($xl, FakeDto::generatePricesInAllCurrencies([],0));

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
        $this->product->attributes->first()->product_attribute_pivot->options()->attach($option->getKey());

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

        // Expected full response
        $this->expected = array_merge($this->expected_short, [
            'description_html' => $this->product->description_html,
            'description_short' => $this->product->description_short,
            'gallery' => [],
            'schemas' => [
                [
                    'name' => 'Rozmiar',
                    'required' => true,
                    'available' => true,
                    //'prices' => [['value' => 0, 'currency' => $this->currency->value]],
                    'metadata' => [],
                    'options' => [
                        [
                            'name' => 'XL',
                            //'prices' => [['value' => 0, 'currency' => $this->currency->value]],
                            'available' => true,
                            'items' => [
                                [
                                    'name' => 'Koszulka XL',
                                    'sku' => 'K001/XL',
                                ],
                            ],
                            'metadata' => [],
                        ],
                        [
                            'name' => 'L',
                            //'prices' => [['value' => 0, 'currency' => $this->currency->value]],
                            'available' => false,
                            'items' => [
                                [
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
                $metadata->name => $metadata->value,
            ],
        ]);

        $this->saleProduct = Product::factory()->create([
            'public' => true,
        ]);
        $this->productService->setProductPrices($this->saleProduct->getKey(), [
            ProductPriceType::PRICE_BASE->value => [PriceDto::from(Money::of(3000, $this->currency->value))],
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
            ->assertJson([
                'data' => [
                    $this->expected_short,
                ],
            ])
            ->assertJsonFragment([
                [
                    'net' => '100.00',
                    'gross' => '100.00',
                    'currency' => 'PLN',
                    'sales_channel_id' => $this->salesChannel->id,
                ],
            ]);

        $this->assertArrayHasKey('translations', $response->json('data.0'));
        $this->assertIsArray($response->json('data.0.translations'));
        $this->assertQueryCountLessThan(26);
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
            ->assertJsonFragment([
                ...$this->expected_short,
                [
                    'net' => '100.00',
                    'gross' => '100.00',
                    'currency' => 'PLN',
                    'sales_channel_id' => $this->salesChannel->id,
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
            ->json('GET', '/products/' . $this->product->slug, ['attribute_slug' => $this->product->attributes->first()->slug])
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
    public function testShowAttribute(string $user): void
    {
        $this->{$user}->givePermissionTo('products.show');

        $product = Product::factory()->create([
            'public' => true,
        ]);

        $attribute = Attribute::factory()->create();

        $option = AttributeOption::factory()->create([
            'index' => 1,
            'attribute_id' => $attribute->getKey(),
        ]);

        $product->attributes()->attach($attribute->getKey());

        $product->attributes->first()->product_attribute_pivot->options()->attach($option->getKey());

        $this
            ->actingAs($this->{$user})
            ->json('GET', '/products', ['attribute_slug' => $attribute->slug])
            ->assertOk()
            ->assertJsonFragment([
                'name' => $attribute->name,
                'slug' => $attribute->slug,
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
    public function testShowAttributes(string $user): void
    {
        $this->{$user}->givePermissionTo('products.show');

        $product = Product::factory()->create([
            'public' => true,
        ]);

        $attribute1 = Attribute::factory()->create();

        $option1 = AttributeOption::factory()->create([
            'index' => 1,
            'attribute_id' => $attribute1->getKey(),
        ]);

        $attribute2 = Attribute::factory()->create([
            'type' => AttributeType::SINGLE_OPTION,
        ]);

        $option2 = AttributeOption::factory()->create([
            'index' => 1,
            'attribute_id' => $attribute2->getKey(),
        ]);

        $attribute3 = Attribute::factory()->create();

        $product->attributes()->attach($attribute1->getKey());
        $product->attributes()->attach($attribute2->getKey());

        $product->attributes->first(fn(Attribute $productAttribute) => $productAttribute->getKey() === $attribute1->getKey())->product_attribute_pivot->options()->attach($option1->getKey());
        $product->attributes->first(fn(Attribute $productAttribute) => $productAttribute->getKey() === $attribute2->getKey())->product_attribute_pivot->options()->attach($option2->getKey());

        $this
            ->actingAs($this->{$user})
            ->json('GET', '/products', ['attribute_slug' => "{$attribute1->slug};{$attribute2->slug}"])
            ->assertOk()
            ->assertJsonFragment([
                'name' => $attribute1->name,
                'slug' => $attribute1->slug,
            ])
            ->assertJsonFragment([
                'id' => $option1->getKey(),
                'name' => $option1->name,
                'index' => $option1->index,
                'value_number' => $option1->value_number,
                'value_date' => $option1->value_date,
                'attribute_id' => $attribute1->getKey(),
            ])
            ->assertJsonFragment([
                'name' => $attribute2->name,
                'slug' => $attribute2->slug,
            ])
            ->assertJsonFragment([
                'id' => $option2->getKey(),
                'name' => $option2->name,
                'index' => $option2->index,
                'value_number' => $option2->value_number,
                'value_date' => $option2->value_date,
                'attribute_id' => $attribute2->getKey(),
            ]);

        $this
            ->actingAs($this->{$user})
            ->json('GET', '/products', [
                'attribute_slug' => "{$attribute1->slug}",
                'attribute' => [
                    $attribute2->slug => $option2->getKey(),
                ],
            ])
            ->assertOk()
            ->assertJsonFragment([
                'name' => $attribute1->name,
                'slug' => $attribute1->slug,
            ])
            ->assertJsonMissing([
                'name' => $attribute2->name,
                'slug' => $attribute2->slug,
            ]);

        $this
            ->actingAs($this->{$user})
            ->json('GET', '/products', [
                'attribute_slug' => "{$attribute3->slug}",
                'attribute' => [
                    $attribute2->slug => $option2->getKey(),
                ],
            ])
            ->assertOk()
            ->assertJsonFragment([
                'attributes' => [],
            ])
            ->assertJsonMissing([
                'name' => $attribute1->name,
                'slug' => $attribute1->slug,
            ])
            ->assertJsonMissing([
                'name' => $attribute2->name,
                'slug' => $attribute2->slug,
            ]);
    }

    /**
     * @dataProvider authProvider
     */
    public function testShowAttributesOrder(string $user): void
    {
        $this->{$user}->givePermissionTo('products.show_details');

        $product = Product::factory()->create([
            'public' => true,
        ]);

        $attribute1 = Attribute::factory()->create([
            'order' => 2,
        ]);

        $option1 = AttributeOption::factory()->create([
            'index' => 1,
            'attribute_id' => $attribute1->getKey(),
        ]);

        $attribute2 = Attribute::factory()->create([
            'type' => AttributeType::SINGLE_OPTION,
            'order' => 0,
        ]);

        $option2 = AttributeOption::factory()->create([
            'index' => 1,
            'attribute_id' => $attribute2->getKey(),
        ]);

        Attribute::factory()->create([
            'order' => 1,
        ]);

        $product->attributes()->attach($attribute1->getKey());
        $product->attributes()->attach($attribute2->getKey());

        $product->attributes->first(fn(Attribute $productAttribute) => $productAttribute->getKey() === $attribute1->getKey())->product_attribute_pivot->options()->attach($option1->getKey());
        $product->attributes->first(fn(Attribute $productAttribute) => $productAttribute->getKey() === $attribute2->getKey())->product_attribute_pivot->options()->attach($option2->getKey());

        $this
            ->actingAs($this->{$user})
            ->json('GET', "/products/id:{$product->getKey()}")
            ->assertOk()
            ->assertJsonPath('data.attributes.0.id', $attribute2->getKey())
            ->assertJsonPath('data.attributes.1.id', $attribute1->getKey());
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
            ->assertJsonFragment([
                'metadata_private' => [
                    $privateMetadata->name => $privateMetadata->value,
                ],
            ]);
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
            ->assertJsonFragment([
                'seo' => [
                    'title' => $seo->title,
                    'no_index' => $noIndex,
                    'description' => $seo->description,
                    'og_image' => null,
                    'twitter_card' => $seo->twitter_card,
                    'keywords' => $seo->keywords,
                    'header_tags' => ['test1', 'test2'],
                    'published' => [$this->lang],
                ],
            ]);
    }

    /**
     * @dataProvider authProvider
     */
    public function testShowWithSales(string $user): void
    {
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
            'target_type' => DiscountTargetType::PRODUCTS,
            'target_is_allow_list' => true,
            'code' => null,
            'percentage' => null,
        ]);
        $this->discountRepository->setDiscountAmounts($sale2->getKey(), [
            PriceDto::from([
                'value' => '10.00',
                'currency' => $this->currency,
            ])
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
                'min_values' => [
                    [
                        'currency' => $this->currency->value,
                        'value' => '20.00',
                    ],
                ],
                'max_values' => [
                    [
                        'currency' => $this->currency->value,
                        'value' => '10000.00',
                    ],
                ],
                'include_taxes' => false,
                'is_in_range' => true,
            ],
        ]);
        $conditionGroup->conditions->first()->pricesMin()->create([
            'value' => '2000',
            'currency' => $this->currency->value,
            'price_type' => DiscountConditionPriceType::PRICE_MIN,
        ]);

        $conditionGroup->conditions->first()->pricesMin()->create([
            'value' => '1000000',
            'currency' => $this->currency->value,
            'price_type' => DiscountConditionPriceType::PRICE_MAX,
        ]);

        $sale5->conditionGroups()->attach($conditionGroup);

        $sale5->products()->attach($this->saleProduct);

        $this->discountService->applyDiscountsOnProduct($this->saleProduct);

        $response = $this->actingAs($this->{$user})
            ->getJson('/products/id:' . $this->saleProduct->getKey());

        $response
            ->assertOk()
            ->assertJsonMissingPath('data.sales')
            ->assertJsonFragment([
                'id' => $this->saleProduct->getKey(),
                'name' => $this->saleProduct->name,
                'price_initial' => [
                    'gross' => '3000.00',
                    'net' => '3000.00',
                    'currency' => Currency::DEFAULT->value,
                    'sales_channel_id' => $this->salesChannel->id,
                ],
                'price' => [
                    'gross' => '2700.00',
                    'net' => '2700.00',
                    'currency' => Currency::DEFAULT->value,
                    'sales_channel_id' => $this->salesChannel->id,
                ],
            ])
            ->assertJsonMissing([
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
    public function testShowDashboardWithSales(string $user): void
    {
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
            'target_type' => DiscountTargetType::PRODUCTS,
            'target_is_allow_list' => true,
            'code' => null,
            'percentage' => null,
        ]);
        $this->discountRepository->setDiscountAmounts($sale2->getKey(), [
            PriceDto::from([
                'value' => '10.00',
                'currency' => $this->currency,
            ])
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
                'min_values' => [
                    [
                        'currency' => $this->currency->value,
                        'value' => '20.00',
                    ],
                ],
                'max_values' => [
                    [
                        'currency' => $this->currency->value,
                        'value' => '10000.00',
                    ],
                ],
                'include_taxes' => false,
                'is_in_range' => true,
            ],
        ]);
        $conditionGroup->conditions->first()->pricesMin()->create([
            'value' => '2000',
            'currency' => $this->currency->value,
            'price_type' => DiscountConditionPriceType::PRICE_MIN,
        ]);

        $conditionGroup->conditions->first()->pricesMin()->create([
            'value' => '1000000',
            'currency' => $this->currency->value,
            'price_type' => DiscountConditionPriceType::PRICE_MAX,
        ]);

        $sale5->conditionGroups()->attach($conditionGroup);

        $sale5->products()->attach($this->saleProduct);

        $this->discountService->applyDiscountsOnProduct($this->saleProduct);

        $response = $this->actingAs($this->{$user})
            ->getJson('/products/id:' . $this->saleProduct->getKey() . '/sales');

        $response
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonMissing([
                'id' => $this->saleProduct->getKey(),
                'name' => $this->saleProduct->name,
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
            ->getJson('/products/id:' . $this->saleProduct->getKey() . '/sales');

        $response
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonMissing([
                'id' => $this->saleProduct->getKey(),
                'name' => $this->saleProduct->name,
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
            'description' => 'Applied - product set is on block list',
            'name' => 'Set not on block list',
            'percentage' => '5',
            'target_type' => DiscountTargetType::PRODUCTS,
            'target_is_allow_list' => false,
            'code' => null,
            'priority' => 0,
        ]);

        $this->discountService->applyDiscountsOnProduct($this->saleProduct);

        $response = $this->actingAs($this->{$user})
            ->getJson('/products/id:' . $this->saleProduct->getKey() . '/sales');

        $response
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonMissing([
                'id' => $this->saleProduct->getKey(),
                'name' => $this->saleProduct->name,
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
            ->getJson('/products/id:' . $this->saleProduct->getKey() . '/sales');

        $response
            ->assertOk()
            ->assertJsonMissing([
                'id' => $this->saleProduct->getKey(),
                'name' => $this->saleProduct->name,
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

        $productPrices = $this->priceService->getCachedProductPrices($product->getKey(), [ProductPriceType::PRICE_MIN], $this->currency);
        $productPricesMin = $productPrices->get(ProductPriceType::PRICE_MIN->value);

        return [
            $product,
            new ProductPriceUpdated(
                $product->getKey(),
                [],
                $productPricesMin->toArray(),
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
                && isset($payload['data']['prices_min_new'])
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

        Bus::assertDispatched(CallQueuedListener::class, fn($job) => $job->class = WebHookEventListener::class);

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

        $this->assertDatabaseHas('schemas', [
            'product_id' => $response->json('data.id'),
            'id' => $schema->getKey(),
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
                'required' => false,
                'options' => [
                    [
                        'name' => 'Default',
                        'prices' => [['value' => $schemaPrice, 'currency' => Currency::DEFAULT->value]],
                    ]
                ]
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
            'price_type' => ProductPriceType::PRICE_MIN,
            'value' => 100 * 100,
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
                'required' => true,
                'options' => [
                    [
                        'name' => 'Default',
                        'prices' => [['value' => $schemaPrice, 'currency' => Currency::DEFAULT->value]],
                    ]
                ]
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
            'price_type' => ProductPriceType::PRICE_MIN,
            'value' => 100 * 100,
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

        Schema::where('product_id', $this->product->id)->delete();

        $schemaPrice = 50;
        $schema = $this->schemaCrudService->store(
            FakeDto::schemaDto([
                'required' => false,
                'product_id' => $this->product->getKey(),
                'options' => [
                    [
                        'name' => 'Default',
                        'prices' => [['value' => $schemaPrice, 'currency' => Currency::DEFAULT->value]],
                    ]
                ]
            ])
        );

        $this->productService->updateMinPrices($this->product);

        $productNewPrice = 250;
        $prices = array_map(fn(Currency $currency) => [
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
            'price_type' => ProductPriceType::PRICE_MIN,
            'value' => $productNewPrice * 100,
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
                'required' => false,
                'product_id' => $this->product->getKey(),
                'options' => [
                    [
                        'name' => 'Default',
                        'prices' => [['value' => $schemaPrice, 'currency' => Currency::DEFAULT->value]],
                    ]
                ]
            ])
        );

        $this->productService->updateMinPrices($this->product);

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
        $prices = array_map(fn(Currency $currency) => [
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
            'price_type' => ProductPriceType::PRICE_MIN_INITIAL->value,
            'value' => $productNewPrice * 100,
        ]);
        $this->assertDatabaseHas('prices', [
            'model_id' => $this->product->getKey(),
            'price_type' => ProductPriceType::PRICE_MIN->value,
            'value' => ($productNewPrice - $saleValue) * 100,
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
     */
    public function testUpdatePrice(string $user): void
    {
        $this->{$user}->givePermissionTo('products.edit');

        Event::fake(ProductPriceUpdated::class);

        $prices = array_map(fn(Currency $currency) => [
            'value' => "5000.00",
            'currency' => $currency->value,
        ], Currency::cases());

        $this->actingAs($this->{$user})->json('PATCH', '/products/id:' . $this->product->getKey(), [
            'prices_base' => $prices
        ])
            ->assertOk();

        Event::assertDispatched(ProductPriceUpdated::class);
    }

    /**
     * @dataProvider authProvider
     */
    public function testUpdateNoProductPriceUpdated(string $user): void
    {
        $this->{$user}->givePermissionTo('products.edit');

        Event::fake(ProductPriceUpdated::class);

        $this->actingAs($this->{$user})->json('PATCH', '/products/id:' . $this->product->getKey(), [
            'prices_base' => $this->productPrices,
        ])
            ->assertOk();

        Event::assertNotDispatched(ProductPriceUpdated::class);
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
                'required' => true,
                'product_id' => $this->product->getKey(),
                'options' => [
                    [
                        'name' => 'Default',
                        'prices' => [['value' => $schemaPrice, 'currency' => Currency::DEFAULT->value]],
                    ]
                ]
            ])
        );

        $this->productService->updateMinPrices($this->product);

        $response = $this->actingAs($this->{$user})->deleteJson('/schemas/id:' . $schema->getKey());
        $response->assertNoContent();

        $this->assertDatabaseHas('prices', [
            'model_id' => $this->product->getKey(),
            'price_type' => ProductPriceType::PRICE_MIN->value,
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

    /**
     * @dataProvider authProvider
     */
    public function testProductHasSchemaOnSchemaDelete(string $user): void
    {
        $this->{$user}->givePermissionTo('schemas.remove');

        Schema::query()->delete();
        $schema = $this->schemaCrudService->store(
            FakeDto::schemaDto([
                'name' => 'test schema',
                'product_id' => $this->product->getKey(),
            ])
        );

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
        $schema = $this->schemaCrudService->store(
            FakeDto::schemaDto([
                'name' => 'test schema',
            ])
        );

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

        $schema = $this->schemaCrudService->store(
            FakeDto::schemaDto([
                'name' => 'test schema',
            ])
        );
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
