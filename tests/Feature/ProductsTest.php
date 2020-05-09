<?php

namespace Tests\Feature;

use App\Item;
use App\Brand;
use App\Product;
use App\Category;
use Tests\TestCase;
use Laravel\Passport\Passport;

class ProductsTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();

        $brand = factory(Brand::class)->create(['public' => true]);
        $category = factory(Category::class)->create(['public' => true]);

        $this->product = factory(Product::class)->create([
            'brand_id' => $brand->id,
            'category_id' => $category->id,
            'public' => true,
        ]);

        $this->product->update([
            'original_id' => $this->product->id,
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
            'item_id' => $this->item->id,
            'extra_price' => 0,
        ]);

        // Hidden
        $brand_hidden = factory(Brand::class)->create(['public' => false]);
        $category_hidden = factory(Category::class)->create(['public' => false]);

        $this->hidden_products = [
            factory(Product::class)->create([
                'brand_id' => $brand->id,
                'category_id' => $category->id,
                'public' => false,
            ]),
            factory(Product::class)->create([
                'brand_id' => $brand_hidden->id,
                'category_id' => $category->id,
                'public' => true,
            ]),
            factory(Product::class)->create([
                'brand_id' => $brand->id,
                'category_id' => $category_hidden->id,
                'public' => true,
            ]),
            factory(Product::class)->create([
                'brand_id' => $brand_hidden->id,
                'category_id' => $category_hidden->id,
                'public' => true,
            ]),
            factory(Product::class)->create([
                'brand_id' => $brand_hidden->id,
                'category_id' => $category->id,
                'public' => false,
            ]),
            factory(Product::class)->create([
                'brand_id' => $brand->id,
                'category_id' => $category_hidden->id,
                'public' => false,
            ]),
            factory(Product::class)->create([
                'brand_id' => $brand_hidden->id,
                'category_id' => $category_hidden->id,
                'public' => false,
            ]),
        ];

        /**
         * Expected short response
         */
        $this->expected_short = [
            'id' => $this->product->id,
            'name' => $this->product->name,
            'slug' => $this->product->slug,
            'price' => $this->product->price,
            'public' => (bool) $this->product->public,
            'digital' => (bool) $this->product->digital,
            'brand' => [
                'id' => $this->product->brand->id,
                'name' => $this->product->brand->name,
                'slug' => $this->product->brand->slug,
                'public' => (bool) $this->product->brand->public,
            ],
            'category' => [
                'id' => $this->product->category->id,
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
                        'quantity' => 0
                    ]
                ]]
            ]],
        ]);

        $this->expectedUpdated = [
            'name' => 'Updated',
            'slug' => 'updated',
            'price' => 150,
            'public' => false,
            'digital' => false,
            'brand' => [
                'id' => $this->product->brand->id,
                'name' => $this->product->brand->name,
                'slug' => $this->product->brand->slug,
                'public' => (bool) $this->product->brand->public,
            ],
            'category' => [
                'id' => $this->product->category->id,
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
                        'quantity' => 0
                    ]
                ]]
            ]],
        ];
    }

    /**
     * @return void
     */
    public function testIndex()
    {
        $response = $this->get('/products');

        $response
            ->assertOk()
            ->assertJsonCount(1, 'data') // Shoud show only public products.
            ->assertJson(['data' => [
                0 => $this->expected_short,
            ]]);
    }

    /**
     * @return void
     */
    public function testView()
    {
        $response = $this->get('/products/' . $this->product->slug);

        $response
            ->assertOk()
            ->assertJson(['data' => $this->expected]);
    }

    /**
     * @return void
     */
    public function testViewHidden()
    {
        foreach ($this->hidden_products as $product) {
            $response = $this->get('/products/' . $product->slug);

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
        $response = $this->post('/products');
        $response->assertUnauthorized();

        Passport::actingAs($this->user);

        $response = $this->post('/products', [
            'name' => 'Test',
            'slug' => 'test',
            'price' => 100.00,
            'brand_id' => $this->product->brand->id,
            'category_id' => $this->product->category->id,
            'description_md' => '# Description',
            'digital' => false,
            'public' => true,
        ]);

        $this->createdId = $response->json()['data']['id'];

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
                    'id' => $this->product->brand->id,
                    'name' => $this->product->brand->name,
                    'slug' => $this->product->brand->slug,
                    'public' => (bool) $this->product->brand->public,
                ],
                'category' => [
                    'id' => $this->product->category->id,
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
                            'quantity' => 0
                        ]
                    ]]
                ]]
            ]]);
    }

    /**
     * @return void
     */
    public function testUpdate()
    {
        $response = $this->patch('/products/id:' . $this->product->id);
        $response->assertUnauthorized();

        Passport::actingAs($this->user);

        $response = $this->patch('/products/id:' . $this->product->id, [
            'name' => 'Updated',
            'slug' => 'updated',
            'price' => 150.00,
            'brand_id' => $this->product->brand->id,
            'category_id' => $this->product->category->id,
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
                            'item_id' => $this->item->id,
                            'extra_price' => 0
                        ]
                    ]
                ]
            ]
        ]);

        $response
            ->assertOk()
            ->assertJson(['data' => $this->expectedUpdated]);
    }

     /**
     * @return void
     */
    public function testDelete()
    {
        $response = $this->delete('/products/id:' . $this->product->id);
        $response->assertUnauthorized();

        Passport::actingAs($this->user);

        $response = $this->delete('/products/id:' . $this->product->id);
        $response->assertNoContent();

        $response = $this->get('/products/id:' . $this->product->id);
        $response->assertNotFound();
    }
}
