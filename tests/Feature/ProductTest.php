<?php

namespace Tests\Feature;

use App\Enums\MediaType;
use App\Models\Media;
use App\Models\Product;
use App\Models\ProductSet;
use App\Models\Schema;
use App\Models\SeoMetadata;
use App\Services\Contracts\ProductServiceContract;
use Illuminate\Support\Facades\App;
use Tests\TestCase;

class ProductTest extends TestCase
{
    private Product $product;
    private Product $hidden_product;

    private array $expected;
    private array $expected_short;

    private ProductServiceContract $productService;

    public function setUp(): void
    {
        parent::setUp();

        $this->productService = App::make(ProductServiceContract::class);

        $this->product = Product::factory()->create([
            'public' => true,
            'order' => 1,
        ]);

        $schema = $this->product->schemas()->create([
            'name' => 'Rozmiar',
            'type' => 'select',
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

        $this->hidden_product = Product::factory()->create([
            'public' => false,
        ]);

        /**
         * Expected short response
         */
        $this->expected_short = [
            'id' => $this->product->getKey(),
            'name' => $this->product->name,
            'slug' => $this->product->slug,
            'price' => (int) $this->product->price,
            'visible' => $this->product->isPublic(),
            'public' => (bool) $this->product->public,
            'available' => true,
            'cover' => null,
        ];

        /**
         * Expected full response
         */
        $this->expected = array_merge($this->expected_short, [
            'description_html' => $this->product->description_html,
            'meta_description' => strip_tags($this->product->description_html),
            'gallery' => [],
            'schemas' => [[
                'name' => 'Rozmiar',
                'type' => 'select',
                'required' => true,
                'available' => true,
                'price' => 0,
                'options' => [
                    [
                        'name' => 'XL',
                        'price' => 0,
                        'disabled' => false,
                        'available' => true,
                        'items' => [[
                            'name' => 'Koszulka XL',
                            'sku' => 'K001/XL',
                        ]],
                    ],
                    [
                        'name' => 'L',
                        'price' => 0,
                        'disabled' => false,
                        'available' => false,
                        'items' => [[
                            'name' => 'Koszulka L',
                            'sku' => 'K001/L',
                        ]],
                    ],
                ],
            ]],
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

        $response = $this->actingAs($this->$user)->getJson('/products?limit=100');
        $response
            ->assertOk()
            ->assertJsonCount(1, 'data') // Should show only public products.
            ->assertJson(['data' => [
                0 => $this->expected_short,
            ]]);

        $this->assertQueryCountLessThan(20);
    }

    /**
     * @dataProvider authProvider
     */
    public function testIndexHidden($user): void
    {
        $this->$user->givePermissionTo(['products.show', 'products.show_hidden']);

        $response = $this->actingAs($this->$user)->getJson('/products');
        $response
            ->assertOk()
            ->assertJsonCount(2, 'data'); // Should show all products.
    }

    /**
     * @dataProvider authProvider
     */
    public function testIndexPerformance($user): void
    {
        $this->$user->givePermissionTo('products.show');

        Product::factory()->count(499)->create([
            'public' => true,
            'order' => 1,
        ]);

        $this
            ->actingAs($this->$user)
            ->getJson('/products?limit=500')
            ->assertOk()
            ->assertJsonCount(500, 'data');

        $this->assertQueryCountLessThan(20);
    }

    /**
     * @dataProvider authProvider
     */
    public function testIndexFullPerformance($user): void
    {
        $this->$user->givePermissionTo('products.show');

        Product::factory()->count(499)->create([
            'public' => true,
            'order' => 1,
        ]);

        $this
            ->actingAs($this->$user)
            ->getJson('/products?limit=500&full')
            ->assertOk()
            ->assertJsonCount(500, 'data');

        $this->assertQueryCountLessThan(20);
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
                ],
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

    public function testCreateUnauthorized(): void
    {
        $this->postJson('/products')->assertForbidden();
    }

    /**
     * @dataProvider authProvider
     */
    public function testCreate($user): void
    {
        $this->$user->givePermissionTo('products.add');

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
            ]]);

        $this->assertDatabaseHas('products', [
            'slug' => 'test',
            'name' => 'Test',
            'price' => 100,
            'public' => true,
            'description_html' => '<h1>Description</h1>',
        ]);
    }

    public function testCreateWithZeroPrice(): void
    {
        $this->user->givePermissionTo('products.add');

        $this
            ->actingAs($this->user)
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

    public function testCreateWithNegativePrice(): void
    {
        $this->user->givePermissionTo('products.add');

        $this
            ->actingAs($this->user)
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
    }

    /**
     * @dataProvider authProvider
     */
    public function testCreateWithSets($user): void
    {
        $this->$user->givePermissionTo('products.add');

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
            ]
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
                    ]
                ]
            ]]);

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
            'type' => 0,
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

    public function testUpdateUnauthorized(): void
    {
        $this->patchJson('/products/id:' . $this->product->getKey())
            ->assertForbidden();
    }

    /**
     * @dataProvider authProvider
     */
    public function testUpdate($user): void
    {
        $this->$user->givePermissionTo('products.edit');

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
    }

    /**
     * @dataProvider authProvider
     */
    public function testUpdateChangeSets($user): void
    {
        $this->$user->givePermissionTo('products.edit');

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
    }

    /**
     * @dataProvider authProvider
     */
    public function testUpdateDeleteSets($user): void
    {
        $this->$user->givePermissionTo('products.edit');

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
        $response = $this->actingAs($this->$user)->patchJson('/schemas/id:' . $schema->getKey() , [
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
        $this->deleteJson('/products/id:' . $this->product->getKey())
            ->assertForbidden();
    }

    /**
     * @dataProvider authProvider
     */
    public function testDelete($user): void
    {
        $this->$user->givePermissionTo('products.remove');

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
