<?php

namespace Tests\Feature;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Item;
use App\Models\Product;
use Laravel\Passport\Passport;
use Tests\TestCase;

class ProductTest extends TestCase
{
    private Product $product;
    private Item $item;

    private array $hidden_products;

    private array $expected;
    private array $expected_short;
    private array $expected_updated;

    public function setUp(): void
    {
        parent::setUp();

        $brand = Brand::factory()->create(['public' => true]);
        $category = Category::factory()->create(['public' => true]);

        $this->product = Product::factory()->create([
            'brand_id' => $brand->getKey(),
            'category_id' => $category->getKey(),
            'public' => true,
        ]);

        $this->product->update([
            'original_id' => $this->product->getKey(),
        ]);

        $schema = $this->product->schemas()->create([
            'name' => null,
            'type' => 0,
            'required' => true,
        ]);

        $this->item = Item::create([
            'name' => $this->product->name,
            'sku' => null,
        ]);

        $schema->schemaItems()->create([
            'item_id' => $this->item->getKey(),
            'extra_price' => 0,
        ]);

        // Hidden
        $brand_hidden = Brand::factory()->create(['public' => false]);
        $category_hidden = Category::factory()->create(['public' => false]);

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
        ];

        /**
         * Expected short response
         */
        $this->expected_short = [
            'id' => $this->product->getKey(),
            'name' => $this->product->name,
            'slug' => $this->product->slug,
            'price' => $this->product->price,
            'visible' => $this->product->isPublic(),
            'public' => (bool) $this->product->public,
            'digital' => (bool) $this->product->digital,
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
            'gallery' => [],
            'schemas' => [[
                'name' => null,
                'type' => 0,
                'required' => true,
                'schema_items' => [[
                    'value' => null,
                    'extra_price' => 0,
                    'item' => [
                        'name' => $this->product->name,
                        'sku' => null,
                        'quantity' => 0,
                    ],
                ]],
            ]],
        ]);

        $this->expected_updated = [
            'name' => 'Updated',
            'slug' => 'updated',
            'price' => 150,
            'public' => false,
            'digital' => false,
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
            'description_md' => '# New description',
            'description_html' => '<h1>New description</h1>',
            'gallery' => [],
            'schemas' => [[
                'name' => null,
                'type' => 0,
                'required' => true,
                'schema_items' => [[
                    'value' => null,
                    'extra_price' => 0,
                    'item' => [
                        'name' => $this->product->name,
                        'sku' => null,
                        'quantity' => 0,
                    ],
                ]],
            ]],
        ];
    }

    /**
     * @return void
     */
    public function testIndex()
    {
        $response = $this->getJson('/products');
        $response
            ->assertOk()
            ->assertJsonCount(1, 'data') // Should show only public products.
            ->assertJson(['data' => [
                0 => $this->expected_short,
            ]]);

        Passport::actingAs($this->user);

        $response = $this->getJson('/products');
        $response
            ->assertOk()
            ->assertJsonCount(count($this->hidden_products) + 1, 'data'); // Should show all products.
    }

    /**
     * @return void
     */
    public function testView()
    {
        $response = $this->getJson('/products/' . $this->product->slug);
        $response
            ->assertOk()
            ->assertJson(['data' => $this->expected]);

        $response = $this->getJson('/products/' . $this->hidden_products[0]->slug);
        $response->assertUnauthorized();
    }

    /**
     * @return void
     */
    public function testViewAdmin()
    {
        $response = $this->getJson('/products/id:' . $this->product->getKey());
        $response->assertUnauthorized();

        Passport::actingAs($this->user);

        $response = $this->getJson('/products/id:' . $this->product->getKey());
        $response
            ->assertOk()
            ->assertJson(['data' => $this->expected]);

        $response = $this->getJson('/products/id:' . $this->hidden_products[0]->getKey());
        $response->assertOk();
    }

    /**
     * @return void
     */
    public function testViewHidden()
    {
        foreach ($this->hidden_products as $product) {
            $response = $this->getJson('/products/' . $product->slug);
            $response
                ->assertUnauthorized()
                ->assertJsonStructure(['error' => [
                    'code',
                    'message',
                ]]);
        }
    }

    /**
     * @return void
     */
    public function testCreate()
    {
        $response = $this->postJson('/products');
        $response->assertUnauthorized();

        Passport::actingAs($this->user);

        $response = $this->postJson('/products', [
            'name' => 'Test',
            'slug' => 'test',
            'price' => 100.00,
            'brand_id' => $this->product->brand->getKey(),
            'category_id' => $this->product->category->getKey(),
            'description_md' => '# Description',
            'digital' => false,
            'public' => true,
        ]);

        $response
            ->assertCreated()
            ->assertJson(['data' => [
                'slug' => 'test',
                'name' => 'Test',
                'price' => 100,
                'public' => true,
                'digital' => false,
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
                'schemas' => [[
                    'name' => null,
                    'type' => 0,
                    'required' => true,
                    'schema_items' => [[
                        'value' => null,
                        'extra_price' => 0,
                        'item' => [
                            'name' => 'Test',
                            'sku' => null,
                            'quantity' => 0,
                        ],
                    ]],
                ]],
            ]]);
    }

    /**
     * @return void
     */
    public function testUpdate()
    {
        $response = $this->patchJson('/products/id:' . $this->product->getKey());
        $response->assertUnauthorized();

        Passport::actingAs($this->user);

        $response = $this->patchJson('/products/id:' . $this->product->getKey(), [
            'name' => 'Updated',
            'slug' => 'updated',
            'price' => 150.00,
            'brand_id' => $this->product->brand->getKey(),
            'category_id' => $this->product->category->getKey(),
            'description_md' => '# New description',
            'digital' => false,
            'public' => false,
            'schemas' => [
                [
                    'name' => null,
                    'type' => 0,
                    'required' => true,
                    'items' => [
                        [
                            'item_id' => $this->item->getKey(),
                            'extra_price' => 0,
                        ],
                    ],
                ],
            ],
        ]);

        $response
            ->assertOk()
            ->assertJson(['data' => $this->expected_updated]);
    }

     /**
     * @return void
     */
    public function testDelete()
    {
        $response = $this->deleteJson('/products/id:' . $this->product->getKey());
        $response->assertUnauthorized();

        Passport::actingAs($this->user);

        $response = $this->deleteJson('/products/id:' . $this->product->getKey());
        $response->assertNoContent();
        $this->assertSoftDeleted($this->product);

        $response = $this->getJson('/products/id:' . $this->product->getKey());
        $response->assertNotFound();
    }
}
