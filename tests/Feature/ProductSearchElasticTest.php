<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\ProductSet;
use App\Models\Tag;
use App\Repositories\Contracts\ProductRepositoryContract;
use App\Repositories\Elastic\ProductRepository;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Config;
use Illuminate\Testing\TestResponse;
use Tests\Support\ElasticTest;
use Tests\TestCase;

class ProductSearchElasticTest extends TestCase
{
    use ElasticTest;

    public function setUp(): void
    {
        parent::setUp();

        Config::set('scout.driver', 'elastic');

        $this->app->bind(
            ProductRepositoryContract::class,
            ProductRepository::class,
        );
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
            'hide_on_index' => true,
        ]);
        $product->sets()->sync([$set->getKey()]);

        $this
            ->actingAs($this->$user)
            ->json('GET', '/products', ['limit' => 100])
            ->assertOk();
//            ->assertJsonCount(1, 'data'); // Should show only public products.

        $this->assertElasticQuery([
            'bool' => [
                'must' => [],
                'should' => [],
                'filter' => [
                    [
                        'term' => [
                            'public' => [
                                'value' => true,
                                'boost' => 1.0,
                            ],
                        ],
                    ],
                    [
                        'term' => [
                            'hide_on_index' => [
                                'value' => false,
                                'boost' => 1.0,
                            ],
                        ],
                    ],
                ],
            ],
        ], 100);

        $this->assertQueryCountLessThan(20);
    }

    /**
     * @dataProvider authProvider
     */
    public function testIndexSortPriceAsc($user): void
    {
        $this->$user->givePermissionTo('products.show');

        Product::factory()->create([
            'public' => true,
            'price' => 1200,
            'price_min' => 1100,
        ]);

        $this
            ->actingAs($this->$user)
            ->json('GET', '/products', ['limit' => 100, 'sort' => 'price:asc'])
            ->assertOk();
//            ->assertJsonCount(1, 'data');

        $this->assertElasticQuery(
            [
                'bool' => [
                    'must' => [],
                    'should' => [],
                    'filter' => [
                        [
                            'term' => [
                                'public' => [
                                    'value' => true,
                                    'boost' => 1.0,
                                ],
                            ],
                        ],
                        [
                            'term' => [
                                'hide_on_index' => [
                                    'value' => false,
                                    'boost' => 1.0,
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            100,
            [
                'sort' => [
                    [
                        'price_min' => 'asc',
                    ],
                ],
            ],
        );

        $this->assertQueryCountLessThan(20);
    }

    /**
     * @dataProvider authProvider
     */
    public function testIndexSortPriceDesc($user): void
    {
        $this->$user->givePermissionTo('products.show');

        Product::factory()->create([
            'public' => true,
            'price' => 1200,
            'price_min' => 1100,
        ]);

        $this
            ->actingAs($this->$user)
            ->json('GET', '/products', ['limit' => 100, 'sort' => 'price:desc'])
            ->assertOk();
//            ->assertJsonCount(1, 'data');

        $this->assertElasticQuery(
            [
                'bool' => [
                    'must' => [],
                    'should' => [],
                    'filter' => [
                        [
                            'term' => [
                                'public' => [
                                    'value' => true,
                                    'boost' => 1.0,
                                ],
                            ],
                        ],
                        [
                            'term' => [
                                'hide_on_index' => [
                                    'value' => false,
                                    'boost' => 1.0,
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            100,
            [
                'sort' => [
                    [
                        'price_max' => 'desc',
                    ],
                ],
            ],
        );

        $this->assertQueryCountLessThan(20);
    }

    /**
     * @dataProvider authProvider
     */
    public function testSearch($user): void
    {
        $this->$user->givePermissionTo('products.show');

        $product = Product::factory()->create([
            'public' => true,
        ]);

        $this
            ->actingAs($this->$user)
            ->json('GET', '/products', ['search' => $product->name])
            ->assertOk();
//            ->assertJsonCount(1, 'data')
//            ->assertJsonFragment(['id' => $product->getKey()]);

        $this->assertElasticQuery([
            'bool' => [
                'must' => [
                    [
                        'multi_match' => [
                            'query' => $product->name,
                            'fuzziness' => 'auto',
                        ],
                    ],
                ],
                'should' => [],
                'filter' => [
                    [
                        'term' => [
                            'public' => [
                                'value' => true,
                                'boost' => 1.0,
                            ],
                        ],
                    ],
                ],
            ],
        ]);
    }

    /**
     * @dataProvider authProvider
     */
    public function testIndexIdsSearch($user): void
    {
        $this->$user->givePermissionTo('products.show');

        $firstProduct = Product::factory()->create([
            'public' => true,
        ]);

        $secondProduct = Product::factory()->create([
            'public' => true,
            'created_at' => Carbon::now()->addHour(),
        ]);

        // Dummy product to check if response will return only 2 products created above
        Product::factory()->create([
            'public' => true,
            'created_at' => Carbon::now()->addHour(),
        ]);

        $this
            ->actingAs($this->$user)
            ->json('GET', '/products', [
                'ids' => "{$firstProduct->getKey()},{$secondProduct->getKey()}",
            ])
            ->assertOk();
//            ->assertJsonCount(2, 'data');

        $this->assertElasticQuery([
            'bool' => [
                'must' => [],
                'should' => [],
                'filter' => [
                    [
                        'terms' => [
                            'id' => [
                                $firstProduct->getKey(),
                                $secondProduct->getKey(),
                            ],
                            'boost' => 1.0,
                        ],
                    ],
                    [
                        'term' => [
                            'public' => [
                                'value' => true,
                                'boost' => 1.0,
                            ],
                        ],
                    ],
                ],
            ],
        ]);
    }

    /**
     * @dataProvider authProvider
     */
    public function testIndexHidden($user): void
    {
        $this->$user->givePermissionTo(['products.show', 'products.show_hidden']);

        $this
            ->actingAs($this->$user)
            ->json('GET', '/products')
            ->assertOk();

        $this->assertElasticQuery([
            'bool' => [
                'must' => [],
                'should' => [],
                'filter' => [],
            ],
        ]);
    }

    /**
     * @dataProvider booleanProvider
     */
    public function testSearchByPublic($user, $boolean, $booleanValue): void
    {
        $this->$user->givePermissionTo('products.show');

        Product::factory()->create([
            'public' => true,
        ]);

        $this
            ->actingAs($this->$user)
            ->json('GET', '/products', ['public' => $boolean])
            ->assertOk();
//            ->assertJsonCount(1, 'data')
//            ->assertJsonFragment(['id' => $product->getKey()]);

        $this->assertElasticQuery([
            'bool' => [
                'must' => [],
                'should' => [],
                'filter' => [
                    [
                        'term' => [
                            'public' => [
                                'value' => $booleanValue,
                                'boost' => 1.0,
                            ],
                        ],
                    ],
                    [
                        'term' => [
                            'public' => [
                                'value' => true,
                                'boost' => 1.0,
                            ],
                        ],
                    ],
                ],
            ],
        ]);
    }

    /**
     * @dataProvider authProvider
     */
    public function testSearchBySet($user): void
    {
        $this->$user->givePermissionTo('products.show');

        $set = ProductSet::factory()->create([
            'public' => true,
            'hide_on_index' => false,
        ]);

        $product = Product::factory()->create([
            'public' => true,
        ]);

        // Product not in set
        Product::factory()->create([
            'public' => true,
        ]);

        $set->products()->attach($product);

        $this
            ->actingAs($this->$user)
            ->json('GET', '/products', ['sets' => [$set->slug]])
            ->assertOk();
//            ->assertJsonCount(1, 'data')
//            ->assertJsonFragment(['id' => $product->getKey()]);

        $this->assertElasticQuery([
            'bool' => [
                'must' => [],
                'should' => [],
                'filter' => [
                    [
                        'terms' => [
                            'sets_slug' => [
                                $set->slug,
                            ],
                            'boost' => 1.0,
                        ],
                    ],
                    [
                        'term' => [
                            'public' => [
                                'value' => true,
                                'boost' => 1.0,
                            ],
                        ],
                    ],
                ],
            ],
        ]);
    }

    /**
     * @dataProvider authProvider
     */
    public function testSearchBySets($user): void
    {
        $this->$user->givePermissionTo('products.show');

        $set = ProductSet::factory()->create([
            'public' => true,
        ]);

        $set2 = ProductSet::factory()->create([
            'public' => true,
        ]);

        $product = Product::factory()->create([
            'public' => true,
        ]);

        $product2 = Product::factory()->create([
            'public' => true,
        ]);

        // Product not in set
        Product::factory()->create([
            'public' => true,
        ]);

        $set->products()->attach($product);
        $set2->products()->attach($product2);

        $this
            ->actingAs($this->$user)
            ->json('GET', '/products', [
                'sets' => [$set->slug, $set2->slug],
            ])
            ->assertOk();
//            ->assertJsonCount(2, 'data')
//            ->assertJsonFragment(['id' => $product->getKey()])
//            ->assertJsonFragment(['id' => $product2->getKey()]);

        $this->assertElasticQuery([
            'bool' => [
                'must' => [],
                'should' => [],
                'filter' => [
                    [
                        'terms' => [
                            'sets_slug' => [
                                $set->slug,
                                $set2->slug,
                            ],
                            'boost' => 1.0,
                        ],
                    ],
                    [
                        'term' => [
                            'public' => [
                                'value' => true,
                                'boost' => 1.0,
                            ],
                        ],
                    ],
                ],
            ],
        ]);
    }

    /**
     * @dataProvider authProvider
     */
    public function testSearchBySetHiddenUnauthorized($user): void
    {
        $this->$user->givePermissionTo('products.show');

        $set = ProductSet::factory()->create([
            'public' => false,
        ]);

        $product = Product::factory()->create([
            'public' => true,
        ]);

        // Product not in set
        Product::factory()->create([
            'public' => true,
        ]);

        $set->products()->attach($product);

        $this
            ->actingAs($this->$user)
            ->json('GET', '/products', ['sets' => [$set->slug]])
            ->assertUnprocessable();
    }

    /**
     * @dataProvider authProvider
     */
    public function testSearchBySetHidden($user): void
    {
        $this->$user->givePermissionTo(['products.show', 'product_sets.show_hidden']);

        $set = ProductSet::factory()->create([
            'public' => true,
        ]);

        // Private set
        ProductSet::factory()->create([
            'public' => false,
        ]);

        $product = Product::factory()->create([
            'public' => true,
        ]);

        // Product not in set
        Product::factory()->create([
            'public' => true,
        ]);

        $set->products()->attach($product);

        $this
            ->actingAs($this->$user)
            ->json('GET', '/products', ['sets' => [$set->slug]])
            ->assertOk();
//            ->assertJsonCount(1, 'data')
//            ->assertJsonFragment(['id' => $product->getKey()]);

        $this->assertElasticQuery([
            'bool' => [
                'must' => [],
                'should' => [],
                'filter' => [
                    [
                        'terms' => [
                            'sets_slug' => [
                                $set->slug,
                            ],
                            'boost' => 1.0,
                        ],
                    ],
                    [
                        'term' => [
                            'public' => [
                                'value' => true,
                                'boost' => 1.0,
                            ],
                        ],
                    ],
                ],
            ],
        ]);
    }

    /**
     * @dataProvider authProvider
     */
    public function testSearchByParentSet($user): void
    {
        $this->$user->givePermissionTo('products.show');

        $this
            ->getProductsByParentSet($this->$user, true, $product)
            ->assertOk();
//            ->assertJsonCount(1, 'data')
//            ->assertJsonFragment(['id' => $product->getKey()]);
    }

    /**
     * @dataProvider authProvider
     */
    public function testSearchByParentSetWithPrivateChildUnauthorized($user): void
    {
        $this->$user->givePermissionTo('products.show');

        $this
            ->getProductsByParentSet($this->$user, false);
//            ->assertOk()
//            ->assertJsonCount(0, 'data');
    }

    /**
     * @dataProvider authProvider
     */
    public function testSearchByParentSetWithPrivateChild($user): void
    {
        $this->$user->givePermissionTo([
            'products.show',
            'product_sets.show_hidden',
        ]);

        $this
            ->getProductsByParentSet($this->$user, false, $product)
            ->assertOk();
//            ->assertJsonCount(1, 'data')
//            ->assertJsonFragment(['id' => $product->getKey()]);
    }

    /**
     * @dataProvider authProvider
     */
    public function testSearchByTag($user): void
    {
        $this->$user->givePermissionTo('products.show');

        $tag = Tag::factory()->create();

        $product = Product::factory()->create([
            'public' => true,
        ]);

        // Product not in tag
        Product::factory()->create([
            'public' => true,
        ]);

        $tag->products()->attach($product);

        $this
            ->actingAs($this->$user)
            ->json('GET', '/products', ['tags' => [$tag->getKey()]])
            ->assertOk();
//            ->assertJsonCount(1, 'data')
//            ->assertJsonFragment(['id' => $product->getKey()]);

        $this->assertElasticQuery([
            'bool' => [
                'must' => [],
                'should' => [],
                'filter' => [
                    [
                        'terms' => [
                            'tags_id' => [
                                $tag->getKey(),
                            ],
                            'boost' => 1.0,
                        ],
                    ],
                    [
                        'term' => [
                            'public' => [
                                'value' => true,
                                'boost' => 1.0,
                            ],
                        ],
                    ],
                ],
            ],
        ]);
    }

    /**
     * @dataProvider authProvider
     */
    public function testSearchByTags($user): void
    {
        $this->$user->givePermissionTo('products.show');

        $tag1 = Tag::factory()->create();

        $product1 = Product::factory()->create([
            'public' => true,
        ]);

        $tag2 = Tag::factory()->create();

        $product2 = Product::factory()->create([
            'public' => true,
        ]);

        // Product not in tag
        Product::factory()->create([
            'public' => true,
        ]);

        $tag1->products()->attach($product1);
        $tag2->products()->attach($product2);

        $this->actingAs($this->$user)
            ->json('GET', '/products', [
                'tags' => [
                    $tag1->getKey(),
                    $tag2->getKey(),
                ],
            ])
            ->assertOk();
//            ->assertJsonCount(2, 'data')
//            ->assertJsonFragment(['id' => $product1->getKey()])
//            ->assertJsonFragment(['id' => $product2->getKey()]);

        $this->assertElasticQuery([
            'bool' => [
                'must' => [],
                'should' => [],
                'filter' => [
                    [
                        'terms' => [
                            'tags_id' => [
                                $tag1->getKey(),
                                $tag2->getKey(),
                            ],
                            'boost' => 1.0,
                        ],
                    ],
                    [
                        'term' => [
                            'public' => [
                                'value' => true,
                                'boost' => 1.0,
                            ],
                        ],
                    ],
                ],
            ],
        ]);
    }

    /**
     * @dataProvider authProvider
     */
    public function testSearchByMetadata($user): void
    {
        $this->$user->givePermissionTo('products.show');

        $this
            ->actingAs($this->$user)
            ->json('GET', '/products?metadata.erp_id=1000&metadata.sku=S001')
            ->assertOk();

        $this->assertElasticQuery([
            'bool' => [
                'must' => [],
                'should' => [],
                'filter' => [
                    [
                        'terms' => [
                            'metadata.name' => [
                                'erp_id',
                                'sku',
                            ],
                            'boost' => 1.0,
                        ],
                    ],
                    [
                        'terms' => [
                            'metadata.value' => [
                                1000,
                                'S001',
                            ],
                            'boost' => 1.0,
                        ],
                    ],
                    [
                        'term' => [
                            'public' => [
                                'value' => true,
                                'boost' => 1.0,
                            ],
                        ],
                    ],
                ],
            ],
        ]);
    }

    /**
     * @dataProvider authProvider
     */
    public function testSearchByMetadataClassicArray($user): void
    {
        $this->$user->givePermissionTo('products.show');

        $this
            ->actingAs($this->$user)
            ->json('GET', '/products?metadata[erp_id]=1000&metadata[sku]=S001')
            ->assertOk();

        $this->assertElasticQuery([
            'bool' => [
                'must' => [],
                'should' => [],
                'filter' => [
                    [
                        'terms' => [
                            'metadata.name' => [
                                'erp_id',
                                'sku',
                            ],
                            'boost' => 1.0,
                        ],
                    ],
                    [
                        'terms' => [
                            'metadata.value' => [
                                1000,
                                'S001',
                            ],
                            'boost' => 1.0,
                        ],
                    ],
                    [
                        'term' => [
                            'public' => [
                                'value' => true,
                                'boost' => 1.0,
                            ],
                        ],
                    ],
                ],
            ],
        ]);
    }

    /**
     * @dataProvider authProvider
     */
    public function testSearchByMetadataPrivate($user): void
    {
        $this->$user->givePermissionTo(['products.show', 'products.show_metadata_private']);

        $this
            ->actingAs($this->$user)
            ->json('GET', '/products?metadata_private.sku=S001')
            ->assertOk();

        $this->assertElasticQuery([
            'bool' => [
                'must' => [],
                'should' => [],
                'filter' => [
                    [
                        'terms' => [
                            'metadata_private.name' => [
                                'sku',
                            ],
                            'boost' => 1.0,
                        ],
                    ],
                    [
                        'terms' => [
                            'metadata_private.value' => [
                                'S001',
                            ],
                            'boost' => 1.0,
                        ],
                    ],
                    [
                        'term' => [
                            'public' => [
                                'value' => true,
                                'boost' => 1.0,
                            ],
                        ],
                    ],
                ],
            ],
        ]);
    }

    /**
     * @dataProvider authProvider
     */
    public function testSearchByMetadataPrivateUnauthorized($user): void
    {
        $this->$user->givePermissionTo('products.show');

        $this
            ->actingAs($this->$user)
            ->json('GET', '/products?metadata_private.sku=S001')
            ->assertUnprocessable();
    }

    /**
     * @dataProvider authProvider
     */
    public function testSearchByPrice($user): void
    {
        $this->$user->givePermissionTo('products.show');

        $this
            ->actingAs($this->$user)
            ->json('GET', '/products', ['price' => ['min' => 100, 'max' => 200]])
            ->assertOk();

        $this->assertElasticQuery([
            'bool' => [
                'must' => [],
                'should' => [],
                'filter' => [
                    [
                        'range' => [
                            'price_min' => [
                                'gte' => 100.0,
                                'boost' => 1.0,
                            ],
                        ],
                    ],
                    [
                        'range' => [
                            'price_max' => [
                                'lte' => 200.0,
                                'boost' => 1.0,
                            ],
                        ],
                    ],
                    [
                        'term' => [
                            'public' => [
                                'value' => true,
                                'boost' => 1.0,
                            ],
                        ],
                    ],
                ],
            ],
        ]);
    }

    private function getProductsByParentSet(
        Authenticatable $user,
        bool $isChildSetPublic,
        ?Product &$productRef = null,
    ): TestResponse {
        $parentSet = ProductSet::factory()->create([
            'public' => true,
        ]);

        $childSet = ProductSet::factory()->create([
            'parent_id' => $parentSet->getKey(),
            'public' => $isChildSetPublic,
        ]);

        $productRef = Product::factory()->create([
            'public' => true,
        ]);

        // Product not in set
        Product::factory()->create([
            'public' => true,
        ]);

        $childSet->products()->attach($productRef);

        $request = $this
            ->actingAs($user)
            ->getJson("/products?sets[]={$parentSet->slug}");

        $this->assertElasticQuery([
            'bool' => [
                'must' => [],
                'should' => [],
                'filter' => [
                    [
                        'terms' => [
                            'sets_slug' => [
                                $parentSet->slug,
                            ],
                            'boost' => 1.0,
                        ],
                    ],
                    [
                        'term' => [
                            'public' => [
                                'value' => true,
                                'boost' => 1.0,
                            ],
                        ],
                    ],
                ],
            ],
        ]);

        return $request;
    }
}
