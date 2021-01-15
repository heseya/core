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
//            'schemas' => [[
//                'name' => null,
//                'type' => 0,
//                'required' => true,
//                'schema_items' => [[
//                    'value' => null,
//                    'extra_price' => 0,
//                    'item' => [
//                        'name' => $this->product->name,
//                        'sku' => null,
//                        'quantity' => 0,
//                    ],
//                ]],
//            ]],
        ]);

        $this->expected_updated = [
            'name' => 'Updated',
            'slug' => 'updated',
            'price' => 150,
            'public' => false,
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
        ];
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

        Passport::actingAs($this->user);

        $response = $this->getJson('/products');
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

        $response = $this->getJson('/products/' . $this->hidden_products[0]->slug);
        $response->assertForbidden();
    }

    public function testShowAdmin(): void
    {
        $response = $this->getJson('/products/id:' . $this->product->getKey());
        $response->assertUnauthorized();

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
            $response->assertForbidden();
        }
    }

    public function testCreate(): void
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
    }

    public function testUpdate(): void
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
            'public' => false,
        ]);

        $response
            ->assertOk()
            ->assertJson(['data' => $this->expected_updated]);
    }

    public function testDelete(): void
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
