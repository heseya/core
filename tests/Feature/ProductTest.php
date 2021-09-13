<?php

namespace Tests\Feature;

use App\Models\ProductSet;
use App\Models\Product;
use App\Models\Schema;
use App\Services\Contracts\MarkdownServiceContract;
use Tests\TestCase;

class ProductTest extends TestCase
{
    private Product $product;

    private array $hidden_products;

    private array $expected;
    private array $expected_short;

    private MarkdownServiceContract $markdownService;

    public function setUp(): void
    {
        parent::setUp();

        $this->markdownService = app(MarkdownServiceContract::class);

        $brand = ProductSet::factory()->create([
            'public' => true,
            'hide_on_index' => false,
        ]);
        $category = ProductSet::factory()->create([
            'public' => true,
            'hide_on_index' => false,
        ]);

        $this->product = Product::factory()->create([
            'brand_id' => $brand->getKey(),
            'category_id' => $category->getKey(),
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

        // Hidden
        $brand_hidden = ProductSet::factory()->create(['public' => false]);
        $category_hidden = ProductSet::factory()->create(['public' => false]);

        $this->hidden_products = [
            Product::factory()->create([
                'brand_id' => $brand->getKey(),
                'category_id' => $category->getKey(),
                'public' => false,
            ]),
            Product::factory()->create([
                'brand_id' => $brand_hidden->getKey(),
                'category_id' => $category->getKey(),
                'public' => true,
            ]),
            Product::factory()->create([
                'brand_id' => $brand->getKey(),
                'category_id' => $category_hidden->getKey(),
                'public' => true,
            ]),
            Product::factory()->create([
                'brand_id' => $brand_hidden->getKey(),
                'category_id' => $category_hidden->getKey(),
                'public' => true,
            ]),
            Product::factory()->create([
                'brand_id' => $brand_hidden->getKey(),
                'category_id' => $category->getKey(),
                'public' => false,
            ]),
            Product::factory()->create([
                'brand_id' => $brand->getKey(),
                'category_id' => $category_hidden->getKey(),
                'public' => false,
            ]),
            Product::factory()->create([
                'brand_id' => $brand_hidden->getKey(),
                'category_id' => $category_hidden->getKey(),
                'public' => false,
            ]),
            Product::factory()->create([
                'brand_id' => null,
                'category_id' => $category->getKey(),
                'public' => false,
            ]),
            Product::factory()->create([
                'brand_id' => $brand->getKey(),
                'category_id' => null,
                'public' => false,
            ]),
            Product::factory()->create([
                'brand_id' => null,
                'category_id' => null,
                'public' => false,
            ]),
        ];

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
            'brand' => [
                'id' => $this->product->brand->getKey(),
                'name' => $this->product->brand->name,
                'slug' => $this->product->brand->slug,
                'public' => (bool) $this->product->brand->public,
            ],
            'category' => [
                'id' => $this->product->category->getKey(),
                'name' => $this->product->category->name,
                'slug' => $this->product->category->slug,
                'public' => (bool) $this->product->category->public,
            ],
            'cover' => null,
        ];

        /**
         * Expected full response
         */
        $this->expected = array_merge($this->expected_short, [
            'description_md' => $this->markdownService->fromHtml($this->product->description_html),
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

    public function testIndex(): void
    {
        $this->user->givePermissionTo('products.show');

        $response = $this->actingAs($this->user)->getJson('/products');
        $response
            ->assertOk()
            ->assertJsonCount(1, 'data') // Should show only public products.
            ->assertJson(['data' => [
                0 => $this->expected_short,
            ]]);
    }

    public function testIndexHidden(): void
    {
        $this->user->givePermissionTo(['products.show', 'products.show_hidden']);

        $response = $this->actingAs($this->user)->getJson('/products');
        $response
            ->assertOk()
            ->assertJsonCount(count($this->hidden_products) + 1, 'data'); // Should show all products.
    }

    public function testShowUnauthorized(): void
    {
        $this->getJson('/products/' . $this->product->slug)
            ->assertForbidden();

        $this->getJson('/products/id:' . $this->product->getKey())
            ->assertForbidden();
    }

    public function testShow(): void
    {
        $this->user->givePermissionTo('products.show_details');

        $response = $this->actingAs($this->user)
            ->getJson('/products/' . $this->product->slug);
        $response
            ->assertOk()
            ->assertJson(['data' => $this->expected]);

        $response = $this->actingAs($this->user)
            ->getJson('/products/id:' . $this->product->getKey());
        $response
            ->assertOk()
            ->assertJson(['data' => $this->expected]);
    }

    public function testShowSets(): void
    {
        $this->user->givePermissionTo('products.show_details');

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

        $response = $this->actingAs($this->user)
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

    public function testShowHiddenUnauthorized(): void
    {
        $this->user->givePermissionTo('products.show_details');

        $response = $this->actingAs($this->user)
            ->getJson('/products/' . $this->hidden_products[0]->slug);
        $response->assertNotFound();

        $response = $this->actingAs($this->user)
            ->getJson('/products/id:' . $this->hidden_products[0]->getKey());
        $response->assertNotFound();
    }

    public function testShowHidden(): void
    {
        $this->user->givePermissionTo(['products.show_details', 'products.show_hidden']);

        $response = $this->actingAs($this->user)
            ->getJson('/products/' . $this->hidden_products[0]->slug);
        $response->assertOk();

        $response = $this->actingAs($this->user)
            ->getJson('/products/id:' . $this->hidden_products[0]->getKey());
        $response->assertOk();
    }

    public function testCreateUnauthorized(): void
    {
        $this->postJson('/products')->assertForbidden();
    }

    public function testCreate(): void
    {
        $this->user->givePermissionTo('products.add');

        $response = $this->actingAs($this->user)->postJson('/products', [
            'name' => 'Test',
            'slug' => 'test',
            'price' => 100.00,
            'brand_id' => $this->product->brand->getKey(),
            'category_id' => $this->product->category->getKey(),
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
                'description_md' => $this->markdownService->fromHtml('<h1>Description</h1>'),
                'description_html' => '<h1>Description</h1>',
                'brand' => [
                    'id' => $this->product->brand->getKey(),
                    'name' => $this->product->brand->name,
                    'slug' => $this->product->brand->slug,
                    'public' => (bool) $this->product->brand->public,
                ],
                'category' => [
                    'id' => $this->product->category->getKey(),
                    'name' => $this->product->category->name,
                    'slug' => $this->product->category->slug,
                    'public' => (bool) $this->product->category->public,
                ],
                'cover' => null,
                'gallery' => [],
            ]]);

        $this->assertDatabaseHas('products', [
            'slug' => 'test',
            'name' => 'Test',
            'price' => 100,
            'public' => true,
            'description_html' => '<h1>Description</h1>',
            'brand_id' => $this->product->brand->getKey(),
            'category_id' => $this->product->category->getKey(),
        ]);
    }

    public function testCreateWithSchemas(): void
    {
        $this->user->givePermissionTo('products.add');

        $schema = Schema::factory()->create();

        $response = $this->actingAs($this->user)->postJson('/products', [
            'name' => 'Test',
            'slug' => 'test',
            'price' => 150,
            'brand_id' => $this->product->brand->getKey(),
            'category_id' => $this->product->category->getKey(),
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
            'brand_id' => $this->product->brand->getKey(),
            'category_id' => $this->product->category->getKey(),
        ]);

        $this->assertDatabaseHas('product_schemas', [
            'product_id' => $product->id,
            'schema_id' => $schema->id,
        ]);
    }

    public function testCreateWithSets(): void
    {
        $this->user->givePermissionTo('products.add');

        $set1 = ProductSet::factory()->create();
        $set2 = ProductSet::factory()->create();

        $response = $this->actingAs($this->user)->postJson('/products', [
            'name' => 'Test',
            'slug' => 'test',
            'price' => 150,
            'brand_id' => $this->product->brand->getKey(),
            'category_id' => $this->product->category->getKey(),
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
            'brand_id' => $this->product->brand->getKey(),
            'category_id' => $this->product->category->getKey(),
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

    public function testUpdateUnauthorized(): void
    {
        $this->patchJson('/products/id:' . $this->product->getKey())
            ->assertForbidden();
    }

    public function testUpdate(): void
    {
        $this->user->givePermissionTo('products.edit');

        $response = $this->actingAs($this->user)->patchJson('/products/id:' . $this->product->getKey(), [
            'name' => 'Updated',
            'slug' => 'updated',
            'price' => 150,
            'brand_id' => $this->product->brand->getKey(),
            'category_id' => $this->product->category->getKey(),
            'description_html' => '<h1>New description</h1>',
            'public' => false,
        ]);

        $response->assertOk();

        $this->assertDatabaseHas('products', [
            'id' => $this->product->getKey(),
            'name' => 'Updated',
            'slug' => 'updated',
            'price' => 150,
            'brand_id' => $this->product->brand->getKey(),
            'category_id' => $this->product->category->getKey(),
            'description_html' => '<h1>New description</h1>',
            'public' => false,
        ]);
    }

    public function testUpdateChangeSets(): void
    {
        $this->user->givePermissionTo('products.edit');

        $product = Product::factory()->create();

        $set1 = ProductSet::factory()->create();
        $set2 = ProductSet::factory()->create();
        $set3 = ProductSet::factory()->create();

        $product->sets()->sync([$set1->getKey(), $set2->getKey()]);

        $response = $this->actingAs($this->user)->patchJson('/products/id:' . $product->getKey(), [
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

    public function testUpdateDeleteSets(): void
    {
        $this->user->givePermissionTo('products.edit');

        $product = Product::factory()->create();

        $set1 = ProductSet::factory()->create();
        $set2 = ProductSet::factory()->create();

        $product->sets()->sync([$set1->getKey(), $set2->getKey()]);

        $response = $this->actingAs($this->user)->patchJson('/products/id:' . $product->getKey(), [
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

    public function testDeleteUnauthorized(): void
    {
        $this->deleteJson('/products/id:' . $this->product->getKey())
            ->assertForbidden();
    }

    public function testDelete(): void
    {
        $this->user->givePermissionTo('products.remove');

        $response = $this->actingAs($this->user)
            ->deleteJson('/products/id:' . $this->product->getKey());
        $response->assertNoContent();
        $this->assertSoftDeleted($this->product);
    }
}
