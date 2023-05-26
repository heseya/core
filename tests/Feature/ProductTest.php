<?php

namespace Tests\Feature;

use App\Enums\AttributeType;
use App\Enums\ConditionType;
use App\Enums\DiscountTargetType;
use App\Enums\DiscountType;
use App\Enums\MediaType;
use App\Enums\MetadataType;
use App\Enums\SchemaType;
use App\Events\ProductCreated;
use App\Events\ProductDeleted;
use App\Events\ProductUpdated;
use App\Listeners\WebHookEventListener;
use App\Models\Attribute;
use App\Models\AttributeOption;
use App\Models\ConditionGroup;
use App\Models\Discount;
use App\Models\Media;
use App\Models\Product;
use App\Models\ProductAttribute;
use App\Models\ProductSet;
use App\Models\Schema;
use App\Models\SeoMetadata;
use App\Models\WebHook;
use App\Services\Contracts\AvailabilityServiceContract;
use App\Services\Contracts\DiscountServiceContract;
use App\Services\Contracts\ProductServiceContract;
use Illuminate\Events\CallQueuedListener;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Spatie\WebhookServer\CallWebhookJob;
use Tests\TestCase;

class ProductTest extends TestCase
{
    private Product $product;
    private Product $hidden_product;

    private array $expected;
    private array $expected_short;

    private ProductServiceContract $productService;
    private DiscountServiceContract $discountService;

    public function setUp(): void
    {
        parent::setUp();

        $this->productService = App::make(ProductServiceContract::class);
        $this->discountService = App::make(DiscountServiceContract::class);

        /** @var AvailabilityServiceContract $availabilityService */
        $availabilityService = App::make(AvailabilityServiceContract::class);

        $this->product = Product::factory()->create([
            'shipping_digital' => false,
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
            'order' => 2,
        ]);

        $l->items()->create([
            'name' => 'Koszulka L',
            'sku' => 'K001/L',
        ]);

        $this->travelBack();

        $xl = $schema->options()->create([
            'name' => 'XL',
            'price' => 0,
            'order' => 1,
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
            'price' => (int) $this->product->price,
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
                $metadata->name => $metadata->value,
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
        ]);
        $product->sets()->sync([$set->getKey()]);

        $this
            ->actingAs($this->$user)
            ->json('GET', '/products', ['limit' => 100])
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJson([
                'data' => [
                    0 => $this->expected_short,
                ],
            ])->assertJsonFragment([
                'price_min' => $this->product->price_min,
                'price_max' => $this->product->price_max,
            ]);

        $this->assertQueryCountLessThan(20);
    }

    /**
     * @dataProvider authProvider
     */
    public function testIndexSortPrice($user): void
    {
        $this->$user->givePermissionTo('products.show');

        $product1 = Product::factory()->create([
            'public' => true,
            'price' => 1200,
            'price_min' => 1100,
        ]);
        $product2 = Product::factory()->create([
            'public' => true,
            'price' => 1300,
            'price_min' => 1050,
        ]);
        $product3 = Product::factory()->create([
            'public' => true,
            'price' => 1500,
            'price_min' => 1000,
        ]);

        $this->product->update([
            'price' => 1500,
            'price_min' => 1200,
        ]);

        $this
            ->actingAs($this->$user)
            ->json('GET', '/products', ['sort' => 'price:asc'])
            ->assertOk()
            ->assertJson([
                'data' => [
                    0 => [
                        'id' => $product3->id,
                        'name' => $product3->name,
                        'price' => $product3->price,
                        'price_min' => $product3->price_min,
                    ],
                    1 => [
                        'id' => $product2->id,
                        'name' => $product2->name,
                        'price' => $product2->price,
                        'price_min' => $product2->price_min,
                    ],
                    2 => [
                        'id' => $product1->id,
                        'name' => $product1->name,
                        'price' => $product1->price,
                        'price_min' => $product1->price_min,
                    ],
                ],
            ]);

        $this->assertQueryCountLessThan(20);
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
            'public' => false,
        ]);

        $product->sets()->sync([$set->getKey()]);

        $this->actingAs($this->$user)
            ->json('GET', '/products')
            ->assertOk()
            ->assertJsonCount(3, 'data'); // Should show all products.
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

        $this
            ->actingAs($this->$user)
            ->getJson('/products/' . $this->product->slug)
            ->assertOk()
            ->assertJson(['data' => $this->expected]);

        $this
            ->actingAs($this->$user)
            ->getJson('/products/id:' . $this->product->getKey())
            ->assertOk()
            ->assertJson(['data' => $this->expected]);
    }

    /**
     * @dataProvider authProvider
     */
    public function testShowWithAttributeMetadata($user): void
    {
        $this->$user->givePermissionTo('products.show_details');

        $this->product->attributes->first()->metadata()->create([
            'name' => 'testMeta',
            'value' => 'testValue',
            'value_type' => MetadataType::STRING,
            'public' => true,
        ]);

        $this
            ->actingAs($this->$user)
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
    public function testShowWrongIdOrSlug($user): void
    {
        $this->$user->givePermissionTo('products.show_details');

        $this->actingAs($this->$user)
            ->getJson('/products/its_wrong_slug')
            ->assertNotFound();

        $this->actingAs($this->$user)
            ->getJson('/products/id:its-not-uuid')
            ->assertNotFound();

        $this->actingAs($this->$user)
            ->getJson('/products/id:' . $this->product->getKey() . $this->product->getKey())
            ->assertNotFound();
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
    public function testShowPrivateSetsNoPermission($user): void
    {
        $this->$user->givePermissionTo('products.show_details');

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
                    'parent_id' => $set1->parent_id,
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
    public function testShowPrivateSetsWithPermission($user): void
    {
        $this->$user->givePermissionTo(['products.show_details', 'product_sets.show_hidden']);

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
            'attribute_id' => $attribute->getKey(),
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
            ],
            ]);
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
     * Sets shouldn't affect product visibility.
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

    /**
     * @dataProvider authProvider
     */
    public function testShowWithSales($user): void
    {
        $this->$user->givePermissionTo('products.show_details');

        $product = Product::factory()->create([
            'public' => true,
            'price' => 3000,
            'price_min_initial' => 2500,
            'price_max_initial' => 3500,
        ]);

        // Applied - product is on list
        $sale1 = Discount::factory()->create([
            'description' => 'Testowa promocja',
            'name' => 'Testowa promocja obowiązująca',
            'value' => 10,
            'type' => DiscountType::PERCENTAGE,
            'target_type' => DiscountTargetType::PRODUCTS,
            'target_is_allow_list' => true,
            'code' => null,
        ]);

        $sale1->products()->attach($product);

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
            'value' => 5,
            'type' => DiscountType::PERCENTAGE,
            'target_type' => DiscountTargetType::ORDER_VALUE,
            'target_is_allow_list' => true,
            'code' => null,
        ]);

        $sale3->products()->attach($product);

        // Not applied - product is on list, but target_is_allow_list = false
        $sale4 = Discount::factory()->create([
            'description' => 'Testowa promocja',
            'name' => 'Not allow list',
            'value' => 5,
            'type' => DiscountType::PERCENTAGE,
            'target_type' => DiscountTargetType::PRODUCTS,
            'target_is_allow_list' => false,
            'code' => null,
        ]);

        $sale4->products()->attach($product);

        // Not applied - invalid condition type in condition group
        $sale5 = Discount::factory()->create([
            'description' => 'Testowa promocja',
            'name' => 'Condition type Order-value',
            'value' => 5,
            'type' => DiscountType::PERCENTAGE,
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

        $sale5->products()->attach($product);

        $this->discountService->applyDiscountsOnProduct($product);

        $response = $this->actingAs($this->$user)
            ->getJson('/products/id:' . $product->getKey());

        $response
            ->assertOk()
            ->assertJsonFragment([
                'id' => $product->getKey(),
                'name' => $product->name,
                'price' => $product->price,
                'price_min_initial' => $product->price_min_initial,
                'price_max_initial' => $product->price_max_initial,
                'price_min' => 2250,
                'price_max' => 3150,
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
    public function testShowWithSalesBlockListEmpty($user): void
    {
        $this->$user->givePermissionTo('products.show_details');

        $product = Product::factory()->create([
            'public' => true,
            'price' => 3000,
            'price_min_initial' => 2500,
            'price_max_initial' => 3500,
        ]);

        // Applied - product is not on block list
        $sale = Discount::factory()->create([
            'description' => 'Testowa promocja',
            'name' => 'Testowa promocja obowiązująca',
            'value' => 10,
            'type' => DiscountType::PERCENTAGE,
            'target_type' => DiscountTargetType::PRODUCTS,
            'target_is_allow_list' => false,
            'code' => null,
        ]);

        $this->discountService->applyDiscountsOnProduct($product);

        $response = $this->actingAs($this->$user)
            ->getJson('/products/id:' . $product->getKey());

        $response
            ->assertOk()
            ->assertJsonFragment([
                'id' => $product->getKey(),
                'name' => $product->name,
                'price' => $product->price,
                'price_min_initial' => $product->price_min_initial,
                'price_max_initial' => $product->price_max_initial,
                'price_min' => 2250,
                'price_max' => 3150,
            ])
            ->assertJsonFragment([
                'id' => $sale->getKey(),
                'name' => $sale->name,
            ]);
    }

    /**
     * @dataProvider authProvider
     */
    public function testShowWithSalesProductSets($user): void
    {
        $this->$user->givePermissionTo('products.show_details');

        $product = Product::factory()->create([
            'public' => true,
            'price' => 3000,
            'price_min_initial' => 2500,
            'price_max_initial' => 3500,
        ]);

        $set = ProductSet::factory()->create([
            'public' => true,
            'order' => 20,
        ]);

        $product->sets()->sync([$set->getKey()]);

        // Applied - product set is on allow list
        $sale1 = Discount::factory()->create([
            'description' => 'Testowa promocja',
            'name' => 'Testowa promocja obowiązująca',
            'value' => 10,
            'type' => DiscountType::PERCENTAGE,
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
            'value' => 5,
            'type' => DiscountType::PERCENTAGE,
            'target_type' => DiscountTargetType::PRODUCTS,
            'target_is_allow_list' => false,
            'code' => null,
        ]);

        $sale2->productSets()->attach($set);

        // Not applied - product set is not on list
        $sale3 = Discount::factory()->create([
            'description' => 'Not applied - product set is not on list',
            'name' => 'Set not on list',
            'value' => 5,
            'type' => DiscountType::PERCENTAGE,
            'target_type' => DiscountTargetType::PRODUCTS,
            'target_is_allow_list' => true,
            'code' => null,
        ]);

        // Applied - product set is not on block list
        $sale4 = Discount::factory()->create([
            'description' => 'Not applied - product set is on block list',
            'name' => 'Set not on block list',
            'value' => 5,
            'type' => DiscountType::PERCENTAGE,
            'target_type' => DiscountTargetType::PRODUCTS,
            'target_is_allow_list' => false,
            'code' => null,
            'priority' => 0,
        ]);

        $this->discountService->applyDiscountsOnProduct($product);

        $response = $this->actingAs($this->$user)
            ->getJson('/products/id:' . $product->getKey());

        $response
            ->assertOk()
            ->assertJsonFragment([
                'id' => $product->getKey(),
                'name' => $product->name,
                'price' => $product->price,
                'price_min_initial' => $product->price_min_initial,
                'price_max_initial' => $product->price_max_initial,
                'price_min' => 2137.5,
                'price_max' => 2992.5,
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
    public function testShowWithSalesProductSetsChildren($user): void
    {
        $this->$user->givePermissionTo('products.show_details');

        $product = Product::factory()->create([
            'public' => true,
            'price' => 3000,
            'price_min_initial' => 2500,
            'price_max_initial' => 3500,
        ]);

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

        $product->sets()->sync([$subChildrenSet->getKey()]);

        // Applied - product set is on allow list
        $sale1 = Discount::factory()->create([
            'description' => 'Testowa promocja',
            'name' => 'Testowa promocja obowiązująca',
            'value' => 10,
            'type' => DiscountType::PERCENTAGE,
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
            'value' => 5,
            'type' => DiscountType::PERCENTAGE,
            'target_type' => DiscountTargetType::PRODUCTS,
            'target_is_allow_list' => false,
            'code' => null,
        ]);

        $sale2->productSets()->attach($parentSet);

        $this->discountService->applyDiscountsOnProduct($product);

        $response = $this->actingAs($this->$user)
            ->getJson('/products/id:' . $product->getKey());

        $response
            ->assertOk()
            ->assertJsonFragment([
                'id' => $product->getKey(),
                'name' => $product->name,
                'price' => $product->price,
                'price_min_initial' => $product->price_min_initial,
                'price_max_initial' => $product->price_max_initial,
                'price_min' => 2250,
                'price_max' => 3150,
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
            'vat_rate' => 23,
            'shipping_digital' => false,
        ]);

        $response
            ->assertCreated()
            ->assertJson(['data' => [
                'slug' => 'test',
                'name' => 'Test',
                'price' => 100,
                'public' => true,
                'vat_rate' => 23,
                'shipping_digital' => false,
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
            'vat_rate' => 23,
            'shipping_digital' => false,
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
            'shipping_digital' => false,
        ]);

        $response
            ->assertCreated()
            ->assertJson(['data' => [
                'slug' => 'test',
                'name' => 'Test',
                'price' => 100,
                'public' => true,
                'shipping_digital' => false,
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
            'shipping_digital' => false,
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
            'shipping_digital' => false,
        ]);

        $response
            ->assertCreated()
            ->assertJson(['data' => [
                'slug' => 'test',
                'name' => 'Test',
                'price' => 100,
                'public' => true,
                'shipping_digital' => false,
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
            'shipping_digital' => false,
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

        WebHook::factory()->create([
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
            'shipping_digital' => false,
        ]);

        $response
            ->assertCreated()
            ->assertJson(['data' => [
                'slug' => 'test',
                'name' => 'Test',
                'price' => 100,
                'public' => false,
                'shipping_digital' => false,
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
            'shipping_digital' => false,
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
            'shipping_digital' => false,
        ]);

        $response
            ->assertCreated()
            ->assertJson(['data' => [
                'slug' => 'test',
                'name' => 'Test',
                'price' => 100,
                'public' => false,
                'shipping_digital' => false,
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
            'shipping_digital' => false,
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
                'shipping_digital' => false,
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
    public function testCreateDigital($user): void
    {
        $this->$user->givePermissionTo('products.add');

        $this
            ->actingAs($this->$user)
            ->postJson('/products', [
                'name' => 'Test',
                'slug' => 'test',
                'price' => 100,
                'public' => true,
                'shipping_digital' => true,
            ])
            ->assertCreated()
            ->assertJson(['data' => [
                'slug' => 'test',
                'name' => 'Test',
                'price' => 100,
                'public' => true,
                'shipping_digital' => true,
            ],
            ]);

        $this->assertDatabaseHas('products', [
            'slug' => 'test',
            'name' => 'Test',
            'price' => 100,
            'shipping_digital' => true,
        ]);
    }

    /**
     * @dataProvider authProvider
     */
    public function testCreateNonDigital($user): void
    {
        $this->$user->givePermissionTo('products.add');

        $this
            ->actingAs($this->$user)
            ->postJson('/products', [
                'name' => 'Test',
                'slug' => 'test',
                'price' => 100,
                'public' => true,
                'shipping_digital' => false,
            ])
            ->assertCreated()
            ->assertJson(['data' => [
                'slug' => 'test',
                'name' => 'Test',
                'price' => 100,
                'public' => true,
                'shipping_digital' => false,
            ],
            ]);

        $this->assertDatabaseHas('products', [
            'slug' => 'test',
            'name' => 'Test',
            'price' => 100,
            'shipping_digital' => false,
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
                'shipping_digital' => false,
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
            'shipping_digital' => false,
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
            'shipping_digital' => false,
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
            'shipping_digital' => false,
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
            'shipping_digital' => false,
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
     * @dataProvider booleanProvider
     */
    public function testCreateWithSeo($user, $boolean, $booleanValue): void
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
            'public' => $boolean,
            'shipping_digital' => false,
            'seo' => [
                'title' => 'seo title',
                'description' => 'seo description',
                'og_image_id' => $media->getKey(),
                'no_index' => $boolean,
            ],
        ]);

        $response
            ->assertCreated()
            ->assertJson(['data' => [
                'slug' => 'test',
                'name' => 'Test',
                'price' => 100,
                'public' => $booleanValue,
                'shipping_digital' => false,
                'description_html' => '<h1>Description</h1>',
                'cover' => null,
                'gallery' => [],
                'seo' => [
                    'title' => 'seo title',
                    'description' => 'seo description',
                    'og_image' => [
                        'id' => $media->getKey(),
                    ],
                    'no_index' => $booleanValue,
                ],
            ],
            ]);

        $this->assertDatabaseHas('products', [
            'slug' => 'test',
            'name' => 'Test',
            'price' => 100,
            'public' => $booleanValue,
            'shipping_digital' => false,
            'description_html' => '<h1>Description</h1>',
        ]);

        $this->assertDatabaseHas('seo_metadata', [
            'title' => 'seo title',
            'description' => 'seo description',
            'model_id' => $response->getData()->data->id,
            'model_type' => Product::class,
            'no_index' => $booleanValue,
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
            'shipping_digital' => false,
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
                'shipping_digital' => false,
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
            'shipping_digital' => false,
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
            'shipping_digital' => false,
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
            'shipping_digital' => false,
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
            'shipping_digital' => false,
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
            'shipping_digital' => false,
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
            'attribute_id' => $attribute->getKey(),
        ]);

        $attribute2 = Attribute::factory()->create();

        $option2 = AttributeOption::factory()->create([
            'index' => 2,
            'attribute_id' => $attribute2->getKey(),
        ]);

        $response = $this
            ->actingAs($this->$user)
            ->postJson('/products', [
                'name' => 'Test',
                'slug' => 'test',
                'price' => 0,
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
    public function testCreateWithAttributeMultipleOptions($user): void
    {
        $this->$user->givePermissionTo('products.add');

        $attribute = Attribute::factory()->create([
            'type' => AttributeType::MULTI_CHOICE_OPTION,
        ]);

        $option = $attribute->options()->create([
            'index' => 1,
        ]);

        $option2 = $attribute->options()->create([
            'index' => 1,
        ]);

        $response = $this
            ->actingAs($this->$user)
            ->postJson('/products', [
                'name' => 'Test',
                'slug' => 'test',
                'price' => 0,
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
            'name' => 'Test',
            'price' => 0,
        ]);

        $product = Product::find($response->getData()->data->id);

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
    public function testCreateWithAttributeInvalidMultipleOptions($user): void
    {
        $this->$user->givePermissionTo('products.add');

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
            ->actingAs($this->$user)
            ->postJson('/products', [
                'name' => 'Test',
                'slug' => 'test',
                'price' => 0,
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
    public function testCreateWithAttributeInvalidOption($user): void
    {
        $this->$user->givePermissionTo('products.add');

        $attribute = Attribute::factory()->create();

        $attribute2 = Attribute::factory()->create();

        $option = $attribute2->options()->create([
            'index' => 1,
        ]);

        $this
            ->actingAs($this->$user)
            ->postJson('/products', [
                'name' => 'Test',
                'slug' => 'test',
                'price' => 0,
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
    public function testCreateWithExistingSale($user): void
    {
        $this->$user->givePermissionTo('products.add');

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

        $response = $this->actingAs($this->$user)->postJson('/products', [
            'name' => 'Test',
            'slug' => 'test',
            'price' => 100.00,
            'public' => true,
            'shipping_digital' => false,
        ]);

        $productId = $response->getData()->data->id;

        $response
            ->assertCreated()
            ->assertJsonFragment([
                'id' => $productId,
                'price_min' => 80,
                'price_max' => 80,
            ])
            ->assertJsonFragment([
                'id' => $saleApplied->getKey(),
            ])
            ->assertJsonMissing([
                'id' => $saleNotApplied->getKey(),
            ]);

        $this->assertDatabaseHas('products', [
            'id' => $productId,
            'price_min' => 80,
            'price_max' => 80,
        ]);
    }

    /**
     * @dataProvider authProvider
     */
    public function testCreateWithGoogleProductCategory($user): void
    {
        $this->$user->givePermissionTo('products.add');

        $response = $this->actingAs($this->$user)->postJson('/products', [
            'name' => 'Test',
            'slug' => 'test',
            'price' => 100.00,
            'description_html' => '<h1>Description</h1>',
            'description_short' => 'So called short description...',
            'public' => true,
            'shipping_digital' => false,
            'google_product_category' => 123,
        ]);

        $response->assertCreated();

        $this->assertDatabaseHas('products', [
            'google_product_category' => 123,
        ]);
    }

    /**
     * @dataProvider authProvider
     */
    public function testCreateWithWrongGoogleProductCategory($user): void
    {
        $this->$user->givePermissionTo('products.add');

        $response = $this->actingAs($this->$user)->postJson('/products', [
            'name' => 'Test',
            'slug' => 'test',
            'price' => 100.00,
            'description_html' => '<h1>Description</h1>',
            'description_short' => 'So called short description...',
            'public' => true,
            'shipping_digital' => false,
            'google_product_category' => 123456,
        ]);

        // no validation
        $response->assertCreated();
    }

    /**
     * @dataProvider authProvider
     */
    public function testCreateWithNullGoogleProductCategory($user): void
    {
        $this->$user->givePermissionTo('products.add');

        $response = $this->actingAs($this->$user)->postJson('/products', [
            'name' => 'Test',
            'slug' => 'test',
            'price' => 100.00,
            'description_html' => '<h1>Description</h1>',
            'description_short' => 'So called short description...',
            'public' => true,
            'shipping_digital' => false,
            'google_product_category' => null,
        ]);

        $response->assertCreated();

        $this->assertDatabaseHas('products', [
            'google_product_category' => null,
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
            'vat_rate' => 5,
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
            'vat_rate' => 5,
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
    public function testUpdateDigital($user): void
    {
        $this->$user->givePermissionTo('products.edit');

        $this
            ->actingAs($this->$user)
            ->patchJson('/products/id:' . $this->product->getKey(), [
                'shipping_digital' => true,
            ])
            ->assertOk()
            ->assertJson(['data' => [
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
    public function testUpdateMinMaxPriceWithSale($user): void
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

        $saleValue = 25;
        $sale = Discount::factory()->create([
            'code' => null,
            'type' => DiscountType::AMOUNT,
            'value' => $saleValue,
            'target_type' => DiscountTargetType::PRODUCTS,
            'target_is_allow_list' => true,
        ]);

        $sale->products()->attach($product->getKey());

        $this->discountService->applyDiscountsOnProduct($product);

        $productNewPrice = 250;
        $response = $this->actingAs($this->$user)->patchJson('/products/id:' . $product->getKey(), [
            'name' => $product->name,
            'slug' => $product->slug,
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
            'price_min_initial' => $productNewPrice,
            'price_max_initial' => $productNewPrice + $schemaPrice,
            'price_min' => $productNewPrice - $saleValue,
            'price_max' => $productNewPrice + $schemaPrice - $saleValue,
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
    public function testUpdateSchemaMinMaxPriceWithSale($user): void
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

        $saleValue = 25;
        $sale = Discount::factory()->create([
            'code' => null,
            'type' => DiscountType::AMOUNT,
            'value' => $saleValue,
            'target_type' => DiscountTargetType::PRODUCTS,
            'target_is_allow_list' => true,
        ]);

        $sale->products()->attach($product->getKey());

        $this->discountService->applyDiscountsOnProduct($product);

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
            'price_min_initial' => $productPrice,
            'price_max_initial' => $productPrice + $schemaNewPrice,
            'price_min' => $productPrice - $saleValue,
            'price_max' => $productPrice + $schemaNewPrice - $saleValue,
        ]);
    }

    /**
     * @dataProvider authProvider
     */
    public function testUpdateWithGoogleProductCategory($user): void
    {
        $this->$user->givePermissionTo('products.edit');

        $response = $this->actingAs($this->$user)->patchJson('/products/id:' . $this->product->getKey(), [
            'google_product_category' => 123,
        ]);

        $response->assertOk();

        $this->assertDatabaseHas('products', [
            'google_product_category' => 123,
        ]);
    }

    /**
     * @dataProvider authProvider
     */
    public function testUpdateWithWrongGoogleProductCategory($user): void
    {
        $this->$user->givePermissionTo('products.edit');

        $response = $this->actingAs($this->$user)->patchJson('/products/id:' . $this->product->getKey(), [
            'google_product_category' => 123456789,
        ]);

        $response->assertOk();
    }

    /**
     * @dataProvider authProvider
     */
    public function testUpdateWithNullGoogleProductCategory($user): void
    {
        $this->$user->givePermissionTo('products.edit');

        $response = $this->actingAs($this->$user)->patchJson('/products/id:' . $this->product->getKey(), [
            'google_product_category' => null,
        ]);

        $response->assertOk();

        $this->assertDatabaseHas('products', [
            'google_product_category' => null,
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

    /**
     * @dataProvider authProvider
     */
    public function testDeleteSchemaMinMaxPriceWithSale($user): void
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

        $saleValue = 25;
        $sale = Discount::factory()->create([
            'code' => null,
            'type' => DiscountType::AMOUNT,
            'value' => $saleValue,
            'target_type' => DiscountTargetType::PRODUCTS,
            'target_is_allow_list' => true,
        ]);

        $sale->products()->attach($product->getKey());

        $this->discountService->applyDiscountsOnProduct($product);

        $response = $this->actingAs($this->$user)->deleteJson('/schemas/id:' . $schema->getKey());

        $response->assertNoContent();

        $this->assertDatabaseHas('products', [
            $product->getKeyName() => $product->getKey(),
            'price' => $productPrice,
            'price_min_initial' => $productPrice,
            'price_max_initial' => $productPrice,
            'price_min' => $productPrice - $saleValue,
            'price_max' => $productPrice - $saleValue,
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
    public function testProductHasSchemaOnSchemaDelete($user): void
    {
        $this->$user->givePermissionTo('schemas.remove');

        Schema::query()->delete();
        $schema = Schema::factory()->create([
            'name' => 'test schema',
        ]);

        $this->product->schemas()->save($schema);
        $this->product->update(['has_schemas' => true]);

        $this->actingAs($this->$user)->json('delete', 'schemas/id:' . $schema->getKey());

        $this->assertDatabaseHas('products', [
            'id' => $this->product->getKey(),
            'has_schemas' => false,
        ]);
    }

    /**
     * @dataProvider authProvider
     */
    public function testProductHasSchemaOnSchemaAdded($user): void
    {
        $this->$user->givePermissionTo('products.edit');

        Schema::query()->delete();
        $schema = Schema::factory()->create([
            'name' => 'test schema',
        ]);

        $this->actingAs($this->$user)->json('patch', 'products/id:' . $this->product->getKey(), [
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
    public function testProductHasSchemaOnSchemasRemovedFromProduct($user): void
    {
        $this->$user->givePermissionTo('products.edit');

        Schema::query()->delete();
        $schema = Schema::factory()->create([
            'name' => 'test schema',
        ]);

        $this->product->schemas()->save($schema);

        $this->product->update(['has_schemas' => true]);

        $this->actingAs($this->$user)->json('patch', 'products/id:' . $this->product->getKey(), [
            'schemas' => [],
        ]);

        $this->assertDatabaseHas('products', [
            'id' => $this->product->getKey(),
            'has_schemas' => false,
        ]);
    }
}
