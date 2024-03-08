<?php

namespace Tests\Feature\Products;

use App\Enums\ConditionType;
use App\Enums\DiscountTargetType;
use App\Enums\MediaType;
use App\Models\ConditionGroup;
use App\Models\Discount;
use App\Models\Media;
use App\Models\Product;
use App\Repositories\Contracts\ProductRepositoryContract;
use App\Repositories\DiscountRepository;
use App\Services\Contracts\DiscountServiceContract;
use Brick\Money\Money;
use Domain\Currency\Currency;
use Domain\Language\Language;
use Domain\Metadata\Enums\MetadataType;
use Domain\Price\Dtos\PriceDto;
use Domain\Price\Enums\DiscountConditionPriceType;
use Domain\Price\Enums\ProductPriceType;
use Domain\ProductAttribute\Enums\AttributeType;
use Domain\ProductAttribute\Models\Attribute;
use Domain\ProductAttribute\Models\AttributeOption;
use Domain\ProductSet\ProductSet;
use Domain\Seo\Models\SeoMetadata;
use Illuminate\Support\Facades\App;

class ProductViewTest extends ProductTestCase
{
    private Product $hidden_product;
    private array $expected;
    private Product $saleProduct;
    private DiscountRepository $discountRepository;
    private DiscountServiceContract $discountService;

    public static function noIndexProvider(): array
    {
        return [
            'as user no index' => ['user', true],
            'as application no index' => ['application', true],
            'as user index' => ['user', false],
            'as application index' => ['application', false],
        ];
    }

    public function setUp(): void
    {
        parent::setUp();

        $productRepository = App::make(ProductRepositoryContract::class);
        $this->discountService = App::make(DiscountServiceContract::class);
        $this->discountRepository = App::make(DiscountRepository::class);

        $this->hidden_product = Product::factory()->create([
            'public' => false,
        ]);

        // Expected full response
        $this->expected = array_merge($this->expected_short, [
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
                            'disabled' => false,
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
                'testMetadata' => 'value metadata',
            ],
        ]);

        $this->saleProduct = Product::factory()->create([
            'public' => true,
        ]);
        $productRepository->setProductPrices($this->saleProduct->getKey(), [
            ProductPriceType::PRICE_BASE->value => [PriceDto::from(Money::of(3000, $this->currency->value))],
            ProductPriceType::PRICE_MIN_INITIAL->value => [PriceDto::from(Money::of(2500, $this->currency->value))],
            ProductPriceType::PRICE_MAX_INITIAL->value => [PriceDto::from(Money::of(3500, $this->currency->value))],
        ]);
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

        $product->attributes->first(fn (Attribute $productAttribute) => $productAttribute->getKey() === $attribute1->getKey())->product_attribute_pivot->options()->attach($option1->getKey());
        $product->attributes->first(fn (Attribute $productAttribute) => $productAttribute->getKey() === $attribute2->getKey())->product_attribute_pivot->options()->attach($option2->getKey());

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

        $product->attributes->first(fn (Attribute $productAttribute) => $productAttribute->getKey() === $attribute1->getKey())->product_attribute_pivot->options()->attach($option1->getKey());
        $product->attributes->first(fn (Attribute $productAttribute) => $productAttribute->getKey() === $attribute2->getKey())->product_attribute_pivot->options()->attach($option2->getKey());

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
            ->assertJsonFragment([
                'id' => $this->saleProduct->getKey(),
                'name' => $this->saleProduct->name,
                'prices_base' => [
                    [
                        'gross' => '3000.00',
                        'net' => '3000.00',
                        'currency' => Currency::DEFAULT->value,
                    ],
                ],
                'prices_min_initial' => [
                    [
                        'gross' => '2500.00',
                        'net' => '2500.00',
                        'currency' => Currency::DEFAULT->value,
                    ],
                ],
                'prices_max_initial' => [
                    [
                        'gross' => '3500.00',
                        'net' => '3500.00',
                        'currency' => Currency::DEFAULT->value,
                    ],
                ],
                'prices_min' => [
                    [
                        'gross' => '2250.00',
                        'net' => '2250.00',
                        'currency' => Currency::DEFAULT->value,
                    ],
                ],
                'prices_max' => [
                    [
                        'gross' => '3150.00',
                        'net' => '3150.00',
                        'currency' => Currency::DEFAULT->value,
                    ],
                ],
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
                'prices_base' => [
                    [
                        'net' => '3000.00',
                        'gross' => '3000.00',
                        'currency' => Currency::DEFAULT->value,
                    ],
                ],
                'prices_min_initial' => [
                    [
                        'net' => '2500.00',
                        'gross' => '2500.00',
                        'currency' => Currency::DEFAULT->value,
                    ],
                ],
                'prices_max_initial' => [
                    [
                        'net' => '3500.00',
                        'gross' => '3500.00',
                        'currency' => Currency::DEFAULT->value,
                    ],
                ],
                'prices_min' => [
                    [
                        'net' => '2250.00',
                        'gross' => '2250.00',
                        'currency' => Currency::DEFAULT->value,
                    ],
                ],
                'prices_max' => [
                    [
                        'net' => '3150.00',
                        'gross' => '3150.00',
                        'currency' => Currency::DEFAULT->value,
                    ],
                ],
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
                'prices_base' => [
                    [
                        'net' => '3000.00',
                        'gross' => '3000.00',
                        'currency' => Currency::DEFAULT->value,
                    ],
                ],
                'prices_min_initial' => [
                    [
                        'net' => '2500.00',
                        'gross' => '2500.00',
                        'currency' => Currency::DEFAULT->value,
                    ],
                ],
                'prices_max_initial' => [
                    [
                        'net' => '3500.00',
                        'gross' => '3500.00',
                        'currency' => Currency::DEFAULT->value,
                    ],
                ],
                'prices_min' => [
                    [
                        'net' => '2137.50',
                        'gross' => '2137.50',
                        'currency' => Currency::DEFAULT->value,
                    ],
                ],
                'prices_max' => [
                    [
                        'net' => '2992.50',
                        'gross' => '2992.50',
                        'currency' => Currency::DEFAULT->value,
                    ],
                ],
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
                'prices_base' => [
                    [
                        'net' => '3000.00',
                        'gross' => '3000.00',
                        'currency' => Currency::DEFAULT->value,
                    ],
                ],
                'prices_min_initial' => [
                    [
                        'net' => '2500.00',
                        'gross' => '2500.00',
                        'currency' => Currency::DEFAULT->value,
                    ],
                ],
                'prices_max_initial' => [
                    [
                        'net' => '3500.00',
                        'gross' => '3500.00',
                        'currency' => Currency::DEFAULT->value,
                    ],
                ],
                'prices_min' => [
                    [
                        'net' => '2250.00',
                        'gross' => '2250.00',
                        'currency' => Currency::DEFAULT->value,
                    ],
                ],
                'prices_max' => [
                    [
                        'net' => '3150.00',
                        'gross' => '3150.00',
                        'currency' => Currency::DEFAULT->value,
                    ],
                ],
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
}
