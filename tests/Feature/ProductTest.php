<?php

namespace Tests\Feature;

use App\Models\ProductSet;
use App\Models\Product;
use App\Models\Schema;
use Tests\TestCase;

class ProductTest extends TestCase
{
    private Product $product;

    private array $hidden_products;

    private array $expected;
    private array $expected_short;

    public function setUp(): void
    {
        parent::setUp();

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
            'description_md' => $this->product->description_md,
            'description_html' => parsedown($this->product->description_md),
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

    public function testIndex(): void
    {
        $response = $this->getJson('/products');
        $response
            ->assertOk()
            ->assertJsonCount(1, 'data') // Should show only public products.
            ->assertJson(['data' => [
                0 => $this->expected_short,
            ]]);
    }

    public function testIndexAdmin(): void
    {
        $response = $this->actingAs($this->user)->getJson('/products');
        $response
            ->assertOk()
            ->assertJsonCount(count($this->hidden_products) + 1, 'data'); // Should show all products.
    }

    public function testShow(): void
    {
        $response = $this->getJson('/products/' . $this->product->slug);
        $response
            ->assertOk()
            ->assertJson(['data' => $this->expected]);
    }

    public function testShowAdminUnauthorized(): void
    {
        $response = $this->getJson('/products/id:' . $this->product->getKey());
        $response->assertUnauthorized();
    }

    public function testShowAdmin(): void
    {
        $response = $this->actingAs($this->user)->getJson('/products/id:' . $this->product->getKey());
        $response
            ->assertOk()
            ->assertJson(['data' => $this->expected]);

        $response = $this->getJson('/products/id:' . $this->hidden_products[0]->getKey());
        $response->assertOk();
    }

    public function testShowHidden(): void
    {
        foreach ($this->hidden_products as $product) {
            $response = $this->getJson('/products/' . $product->slug);
            $response->assertNotFound();
        }
    }

    public function testCreateUnauthorized(): void
    {
        $response = $this->postJson('/products');
        $response->assertUnauthorized();
    }

    public function testCreate(): void
    {
        $response = $this->actingAs($this->user)->postJson('/products', [
            'name' => 'Test',
            'slug' => 'test',
            'price' => 100.00,
            'brand_id' => $this->product->brand->getKey(),
            'category_id' => $this->product->category->getKey(),
            'description_md' => '# Description',
            'public' => true,
        ]);

        $response
            ->assertCreated()
            ->assertJson(['data' => [
                'slug' => 'test',
                'name' => 'Test',
                'price' => 100,
                'public' => true,
                'description_md' => '# Description',
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
            'description_md' => '# Description',
            'brand_id' => $this->product->brand->getKey(),
            'category_id' => $this->product->category->getKey(),
        ]);
    }

    public function testCreateWithSchemas(): void
    {
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
            'description_md' => null,
            'brand_id' => $this->product->brand->getKey(),
            'category_id' => $this->product->category->getKey(),
        ]);

        $this->assertDatabaseHas('product_schemas', [
            'product_id' => $product->id,
            'schema_id' => $schema->id,
        ]);
    }

    public function testUpdateUnauthorized(): void
    {
        $response = $this->patchJson('/products/id:' . $this->product->getKey());
        $response->assertUnauthorized();
    }

    public function testUpdate(): void
    {
        $response = $this->actingAs($this->user)->patchJson('/products/id:' . $this->product->getKey(), [
            'name' => 'Updated',
            'slug' => 'updated',
            'price' => 150,
            'brand_id' => $this->product->brand->getKey(),
            'category_id' => $this->product->category->getKey(),
            'description_md' => '# New description',
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
            'description_md' => '# New description',
            'public' => false,
        ]);
    }

    public function testDeleteUnauthorized(): void
    {
        $response = $this->deleteJson('/products/id:' . $this->product->getKey());
        $response->assertUnauthorized();
    }

    public function testDelete(): void
    {
        $response = $this->actingAs($this->user)->deleteJson('/products/id:' . $this->product->getKey());
        $response->assertNoContent();
        $this->assertSoftDeleted($this->product);
    }
}
