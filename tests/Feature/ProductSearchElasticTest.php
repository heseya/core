<?php

namespace Tests\Feature;

use App\Models\Attribute;
use App\Models\AttributeOption;
use App\Models\ProductSet;
use App\Repositories\Contracts\ProductRepositoryContract;
use App\Repositories\Elastic\ProductRepository;
use Illuminate\Support\Facades\Config;
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

        $this
            ->actingAs($this->$user)
            ->json('GET', '/products', ['limit' => 100])
            ->assertOk();

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

        $this
            ->actingAs($this->$user)
            ->json('GET', '/products', ['limit' => 100, 'sort' => 'price:asc'])
            ->assertOk();

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

        $this
            ->actingAs($this->$user)
            ->json('GET', '/products', ['limit' => 100, 'sort' => 'price:desc'])
            ->assertOk();

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
        $searchQuery = 'search';

        $this->$user->givePermissionTo('products.show');

        $this
            ->actingAs($this->$user)
            ->json('GET', '/products', ['search' => $searchQuery])
            ->assertOk();

        $this->assertElasticQuery([
            'bool' => [
                'must' => [
                    [
                        'multi_match' => [
                            'query' => $searchQuery,
                            'fuzziness' => 'auto',
                            'fields' => [
                                'name^10',
                                'attributes.*^5',
                                '*',
                            ],
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
        $uuid1 = '123e4567-e89b-12d3-a456-426655440000';
        $uuid2 = '123e4567-e89b-12d3-a456-426655440001';

        $this->$user->givePermissionTo('products.show');

        $this
            ->actingAs($this->$user)
            ->json('GET', '/products', [
                'ids' => "{$uuid1},{$uuid2}",
            ])
            ->assertOk();

        $this->assertElasticQuery([
            'bool' => [
                'must' => [],
                'should' => [],
                'filter' => [
                    [
                        'terms' => [
                            'id' => [
                                $uuid1,
                                $uuid2,
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

        $this
            ->actingAs($this->$user)
            ->json('GET', '/products', ['public' => $boolean])
            ->assertOk();

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
        $set = ProductSet::factory()->create([
            'public' => true,
        ]);

        $this->$user->givePermissionTo('products.show');

        $this
            ->actingAs($this->$user)
            ->json('GET', '/products', ['sets' => [$set->slug]])
            ->assertOk();

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
    public function testSearchBySetNegation($user): void
    {
        $set = ProductSet::factory()->create([
            'public' => true,
        ]);

        $this->$user->givePermissionTo('products.show');

        $this
            ->actingAs($this->$user)
            ->json('GET', '/products', ['sets_not' => [$set->slug]])
            ->assertOk();

        $this->assertElasticQuery([
            'bool' => [
                'must' => [],
                'should' => [],
                'filter' => [
                    [
                        'bool' => [
                            'must_not' => [
                                'terms' => [
                                    'sets_slug' => [
                                        $set->slug,
                                    ],
                                    'boost' => 1.0,
                                ],
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
    public function testSearchBySets($user): void
    {
        $set = ProductSet::factory()->create([
            'public' => true,
        ]);

        $set2 = ProductSet::factory()->create([
            'public' => true,
        ]);

        $this->$user->givePermissionTo('products.show');

        $this
            ->actingAs($this->$user)
            ->json('GET', '/products', [
                'sets' => [$set->slug, $set2->slug],
            ])
            ->assertOk();

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
    public function testSearchBySetsNegation($user): void
    {
        $set = ProductSet::factory()->create([
            'public' => true,
        ]);

        $set2 = ProductSet::factory()->create([
            'public' => true,
        ]);

        $this->$user->givePermissionTo('products.show');

        $this
            ->actingAs($this->$user)
            ->json('GET', '/products', [
                'sets_not' => [$set->slug, $set2->slug],
            ])
            ->assertOk();

        $this->assertElasticQuery([
            'bool' => [
                'must' => [],
                'should' => [],
                'filter' => [
                    [
                        'bool' => [
                            'must_not' => [
                                'terms' => [
                                    'sets_slug' => [
                                        $set->slug,
                                        $set2->slug,
                                    ],
                                    'boost' => 1.0,
                                ],
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
    public function testSearchBySetHiddenUnauthorized($user): void
    {
        $set = ProductSet::factory()->create([
            'public' => false,
        ]);

        $this->$user->givePermissionTo('products.show');

        $this
            ->actingAs($this->$user)
            ->json('GET', '/products', ['sets' => [$set->slug]])
            ->assertUnprocessable();
    }

    /**
     * @dataProvider authProvider
     */
    public function testSearchByNonExistingSet($user): void
    {
        $slug = 'non-existing-set';

        $this->$user->givePermissionTo('products.show');

        $this
            ->actingAs($this->$user)
            ->json('GET', '/products', ['sets' => [$slug]])
            ->assertUnprocessable();
    }

    /**
     * @dataProvider authProvider
     */
    public function testSearchBySetHidden($user): void
    {
        $set = ProductSet::factory()->create([
            'public' => true,
        ]);

        $this->$user->givePermissionTo(['products.show', 'product_sets.show_hidden']);

        $this
            ->actingAs($this->$user)
            ->json('GET', '/products', ['sets' => [$set->slug]])
            ->assertOk();

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
    public function testSearchByTag($user): void
    {
        $uuid = 'a2f4f8b0-f8c9-11e9-9eb6-2a2ae2dbcce4';

        $this->$user->givePermissionTo('products.show');

        $this
            ->actingAs($this->$user)
            ->json('GET', '/products', ['tags' => [$uuid]])
            ->assertOk();

        $this->assertElasticQuery([
            'bool' => [
                'must' => [],
                'should' => [],
                'filter' => [
                    [
                        'terms' => [
                            'tags_id' => [
                                $uuid,
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
    public function testSearchByTagNegation($user): void
    {
        $uuid = 'a2f4f8b0-f8c9-11e9-9eb6-2a2ae2dbcce4';

        $this->$user->givePermissionTo('products.show');

        $this
            ->actingAs($this->$user)
            ->json('GET', '/products', ['tags_not' => [$uuid]])
            ->assertOk();

        $this->assertElasticQuery([
            'bool' => [
                'must' => [],
                'should' => [],
                'filter' => [
                    [
                        'bool' => [
                            'must_not' => [
                                'terms' => [
                                    'tags_id' => [
                                        $uuid,
                                    ],
                                    'boost' => 1.0,
                                ],
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
    public function testSearchByTags($user): void
    {
        $uuid1 = 'a2f4f8b0-f8c9-11e9-9eb6-2a2ae2dbcce4';
        $uuid2 = 'a2f4f8b1-f8c9-11e9-9eb6-2a2ae2dbcce4';

        $this->$user->givePermissionTo('products.show');

        $this->actingAs($this->$user)
            ->json('GET', '/products', [
                'tags' => [
                    $uuid1,
                    $uuid2,
                ],
            ])
            ->assertOk();

        $this->assertElasticQuery([
            'bool' => [
                'must' => [],
                'should' => [],
                'filter' => [
                    [
                        'terms' => [
                            'tags_id' => [
                                $uuid1,
                                $uuid2,
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
    public function testSearchByTagsNegation($user): void
    {
        $uuid1 = 'a2f4f8b0-f8c9-11e9-9eb6-2a2ae2dbcce4';
        $uuid2 = 'a2f4f8b1-f8c9-11e9-9eb6-2a2ae2dbcce4';

        $this->$user->givePermissionTo('products.show');

        $this->actingAs($this->$user)
            ->json('GET', '/products', [
                'tags_not' => [
                    $uuid1,
                    $uuid2,
                ],
            ])
            ->assertOk();

        $this->assertElasticQuery([
            'bool' => [
                'must' => [],
                'should' => [],
                'filter' => [
                    [
                        'bool' => [
                            'must_not' => [
                                'terms' => [
                                    'tags_id' => [
                                        $uuid1,
                                        $uuid2,
                                    ],
                                    'boost' => 1.0,
                                ],
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
    public function testSearchByMetadata($user): void
    {
        $erpId = 'a2f4f8b0-f8c9-11e9-9eb6-2a2ae2dbcce4';
        $sku = 123456789;

        $this->$user->givePermissionTo('products.show');

        $this
            ->actingAs($this->$user)
            ->json('GET', "/products?metadata.erp_id={$erpId}&metadata.sku={$sku}")
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
                                $erpId,
                                $sku,
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
        $erpId = 'a2f4f8b0-f8c9-11e9-9eb6-2a2ae2dbcce4';
        $sku = 123456789;

        $this->$user->givePermissionTo('products.show');

        $this
            ->actingAs($this->$user)
            ->json('GET', "/products?metadata[erp_id]={$erpId}&metadata[sku]={$sku}")
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
                                $erpId,
                                $sku,
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
    public function testSearchByMetadataBodyParams($user): void
    {
        $erpId = 'a2f4f8b0-f8c9-11e9-9eb6-2a2ae2dbcce4';
        $sku = 123456789;

        $this->$user->givePermissionTo('products.show');

        $this
            ->actingAs($this->$user)
            ->json(
                'GET',
                '/products',
                [
                    'metadata' => [
                        'erp_id' => $erpId,
                        'sku' => $sku,
                    ],
                ],
            )
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
                                $erpId,
                                $sku,
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
        $sku = 123456789;

        $this->$user->givePermissionTo(['products.show', 'products.show_metadata_private']);

        $this
            ->actingAs($this->$user)
            ->json('GET', "/products?metadata_private.sku={$sku}")
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
                                $sku,
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
        $sku = 123456789;

        $this->$user->givePermissionTo('products.show');

        $this
            ->actingAs($this->$user)
            ->json('GET', "/products?metadata_private.sku={$sku}")
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

    /**
     * @dataProvider authProvider
     */
    public function testSearchByPriceZero($user): void
    {
        $this->$user->givePermissionTo('products.show');

        $this
            ->actingAs($this->$user)
            ->json('GET', '/products', ['price' => ['min' => 0]])
            ->assertOk();

        $this->assertElasticQuery([
            'bool' => [
                'must' => [],
                'should' => [],
                'filter' => [
                    [
                        'range' => [
                            'price_min' => [
                                'gte' => 0.0,
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
    public function testSearchByAttributeId($user): void
    {
        $this->$user->givePermissionTo('products.show');

        $attribute = Attribute::factory()->create([
            'name' => 'Serie',
            'slug' => 'serie',
            'sortable' => 1,
            'type' => 'multi-choice-option',
        ]);

        $option1 = AttributeOption::factory()->create([
            'attribute_id' => $attribute->getKey(),
            'index' => 1,
        ]);

        $option2 = AttributeOption::factory()->create([
            'attribute_id' => $attribute->getKey(),
            'index' => 1,
        ]);

        $this
            ->actingAs($this->$user)
            ->json('GET', '/products', ['attribute' => [$attribute->slug => $option1->getKey()]])
            ->assertOk();

        $this->assertElasticQuery([
            'bool' => [
                'must' => [],
                'should' => [],
                'filter' => [
                    [
                        'terms' => [
                            'attributes_slug' => [
                                $attribute->slug,
                            ],
                            'boost' => 1.0,
                        ],
                    ],
                    [
                        'nested' => [
                            'path' => 'attributes.values',
                            'query' => [
                                'terms' => [
                                    'attributes.values.id' => [
                                        $option1->getKey(),
                                    ],
                                    'boost' => 1.0,
                                ],
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
        //Case: Attributes as array
        $this
            ->actingAs($this->$user)
            ->json('GET', '/products', [
                'attribute' => [
                    $attribute->slug => [
                        $option1->getKey(),
                        $option2->getKey(),
                    ],
                ],
            ])
            ->assertOk();

        $this->assertElasticQuery([
            'bool' => [
                'must' => [],
                'should' => [],
                'filter' => [
                    [
                        'terms' => [
                            'attributes_slug' => [
                                $attribute->slug,
                            ],
                            'boost' => 1.0,
                        ],
                    ],
                    [
                        'nested' => [
                            'path' => 'attributes.values',
                            'query' => [
                                'terms' => [
                                    'attributes.values.id' => [
                                        $option1->getKey(),
                                        $option2->getKey(),
                                    ],
                                    'boost' => 1.0,
                                ],
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
        //Case: Attributes as string - coma as delimiter
        $this
            ->actingAs($this->$user)
            ->json('GET', '/products', [
                'attribute' => [
                    $attribute->slug => $option1->getKey() . ',' . $option2->getKey(),
                ],
            ])
            ->assertOk();

        $this->assertElasticQuery([
            'bool' => [
                'must' => [],
                'should' => [],
                'filter' => [
                    [
                        'terms' => [
                            'attributes_slug' => [
                                $attribute->slug,
                            ],
                            'boost' => 1.0,
                        ],
                    ],
                    [
                        'nested' => [
                            'path' => 'attributes.values',
                            'query' => [
                                'terms' => [
                                    'attributes.values.id' => [
                                        $option1->getKey(),
                                        $option2->getKey(),
                                    ],
                                    'boost' => 1.0,
                                ],
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
    public function testSearchByAttributeIdNegation($user): void
    {
        $this->$user->givePermissionTo('products.show');

        $attribute = Attribute::factory()->create([
            'name' => 'Serie',
            'slug' => 'serie',
            'sortable' => 1,
            'type' => 'multi-choice-option',
        ]);

        $option1 = AttributeOption::factory()->create([
            'attribute_id' => $attribute->getKey(),
            'index' => 1,
        ]);

        $option2 = AttributeOption::factory()->create([
            'attribute_id' => $attribute->getKey(),
            'index' => 1,
        ]);

        $this
            ->actingAs($this->$user)
            ->json('GET', '/products', ['attribute_not' => [$attribute->slug => $option1->getKey()]])
            ->assertOk();

        $this->assertElasticQuery([
            'bool' => [
                'must' => [],
                'should' => [],
                'filter' => [
                    [
                        'terms' => [
                            'attributes_slug' => [
                                $attribute->slug,
                            ],
                            'boost' => 1.0,
                        ],
                    ],
                    [
                        'bool' => [
                            'must_not' => [
                                'nested' => [
                                    'path' => 'attributes.values',
                                    'query' => [
                                        'terms' => [
                                            'attributes.values.id' => [
                                                $option1->getKey(),
                                            ],
                                            'boost' => 1.0,
                                        ],
                                    ],
                                ],
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
        //Case: Attributes as array
        $this
            ->actingAs($this->$user)
            ->json('GET', '/products', [
                'attribute_not' => [
                    $attribute->slug => [
                        $option1->getKey(),
                        $option2->getKey(),
                    ],
                ],
            ])
            ->assertOk();

        $this->assertElasticQuery([
            'bool' => [
                'must' => [],
                'should' => [],
                'filter' => [
                    [
                        'terms' => [
                            'attributes_slug' => [
                                $attribute->slug,
                            ],
                            'boost' => 1.0,
                        ],
                    ],
                    [
                        'bool' => [
                            'must_not' => [
                                'nested' => [
                                    'path' => 'attributes.values',
                                    'query' => [
                                        'terms' => [
                                            'attributes.values.id' => [
                                                $option1->getKey(),
                                                $option2->getKey(),
                                            ],
                                            'boost' => 1.0,
                                        ],
                                    ],
                                ],
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
        //Case: Attributes as string - coma as delimiter
        $this
            ->actingAs($this->$user)
            ->json('GET', '/products', [
                'attribute_not' => [
                    $attribute->slug => $option1->getKey() . ',' . $option2->getKey(),
                ],
            ])
            ->assertOk();

        $this->assertElasticQuery([
            'bool' => [
                'must' => [],
                'should' => [],
                'filter' => [
                    [
                        'terms' => [
                            'attributes_slug' => [
                                $attribute->slug,
                            ],
                            'boost' => 1.0,
                        ],
                    ],
                    [
                        'bool' => [
                            'must_not' => [
                                'nested' => [
                                    'path' => 'attributes.values',
                                    'query' => [
                                        'terms' => [
                                            'attributes.values.id' => [
                                                $option1->getKey(),
                                                $option2->getKey(),
                                            ],
                                            'boost' => 1.0,
                                        ],
                                    ],
                                ],
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
    public function testSearchByAttributeIdInvalidOption($user): void
    {
        $this->$user->givePermissionTo('products.show');

        $optionId = 'a2f4f8b0-f8c9-11e9-9eb6-2a2ae2dbcce4';

        $attribute = Attribute::factory()->create([
            'name' => 'Serie',
            'slug' => 'serie',
            'sortable' => 1,
            'type' => 'multi-choice-option',
        ]);

        $this
            ->actingAs($this->$user)
            ->json('GET', '/products', ['attribute' => [$attribute->slug => $optionId]])
            ->assertUnprocessable();
    }

    /**
     * @dataProvider authProvider
     */
    public function testSearchByAttributeIdInvalidAttribute($user): void
    {
        $this->$user->givePermissionTo('products.show');

        $slug = 'serie';
        $attribute = Attribute::factory()->create([
            'sortable' => 1,
        ]);

        $option = AttributeOption::factory()->create([
            'attribute_id' => $attribute->getKey(),
            'index' => 1,
        ]);

        $this
            ->actingAs($this->$user)
            ->json('GET', '/products', ['attribute' => [$slug => $option->getKey()]])
            ->assertUnprocessable();
    }

    /**
     * @dataProvider authProvider
     */
    public function testSearchByAttributeNumber($user): void
    {
        $this->$user->givePermissionTo('products.show');

        $attribute = Attribute::factory()->create([
            'sortable' => 1,
            'type' => 'number',
        ]);

        $this
            ->actingAs($this->$user)
            ->json(
                'GET',
                '/products',
                [
                    'attribute' => [
                        $attribute->slug => [
                            'min' => 1337,
                            'max' => 2137,
                        ],
                    ],
                ]
            )
            ->assertOk();

        $this->assertElasticQuery([
            'bool' => [
                'must' => [],
                'should' => [],
                'filter' => [
                    [
                        'terms' => [
                            'attributes_slug' => [
                                $attribute->slug,
                            ],
                            'boost' => 1.0,
                        ],
                    ],
                    [
                        'nested' => [
                            'path' => 'attributes.values',
                            'query' => [
                                'range' => [
                                    'attributes.values.value_number' => [
                                        'gte' => 1337,
                                        'lte' => 2137,
                                        'boost' => 1.0,
                                    ],
                                ],
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

        $this
            ->actingAs($this->$user)
            ->json(
                'GET',
                '/products',
                [
                    'attribute' => [
                        $attribute->slug => [
                            'min' => 1337,
                        ],
                    ],
                ]
            )
            ->assertOk();

        $this->assertElasticQuery([
            'bool' => [
                'must' => [],
                'should' => [],
                'filter' => [
                    [
                        'terms' => [
                            'attributes_slug' => [
                                $attribute->slug,
                            ],
                            'boost' => 1.0,
                        ],
                    ],
                    [
                        'nested' => [
                            'path' => 'attributes.values',
                            'query' => [
                                'range' => [
                                    'attributes.values.value_number' => [
                                        'gte' => 1337,
                                        'boost' => 1.0,
                                    ],
                                ],
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

        $this
            ->actingAs($this->$user)
            ->json(
                'GET',
                '/products',
                [
                    'attribute' => [
                        $attribute->slug => [
                            'max' => 2137,
                        ],
                    ],
                ]
            )
            ->assertOk();

        $this->assertElasticQuery([
            'bool' => [
                'must' => [],
                'should' => [],
                'filter' => [
                    [
                        'terms' => [
                            'attributes_slug' => [
                                $attribute->slug,
                            ],
                            'boost' => 1.0,
                        ],
                    ],
                    [
                        'nested' => [
                            'path' => 'attributes.values',
                            'query' => [
                                'range' => [
                                    'attributes.values.value_number' => [
                                        'lte' => 2137,
                                        'boost' => 1.0,
                                    ],
                                ],
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
    public function testSearchByAttributeNumberNegation($user): void
    {
        $this->$user->givePermissionTo('products.show');

        $attribute = Attribute::factory()->create([
            'sortable' => 1,
            'type' => 'number',
        ]);

        $this
            ->actingAs($this->$user)
            ->json(
                'GET',
                '/products',
                [
                    'attribute_not' => [
                        $attribute->slug => [
                            'min' => 1337,
                            'max' => 2137,
                        ],
                    ],
                ]
            )
            ->assertOk();

        $this->assertElasticQuery([
            'bool' => [
                'must' => [],
                'should' => [],
                'filter' => [
                    [
                        'terms' => [
                            'attributes_slug' => [
                                $attribute->slug,
                            ],
                            'boost' => 1.0,
                        ],
                    ],
                    [
                        'bool' => [
                            'must_not' => [
                                'nested' => [
                                    'path' => 'attributes.values',
                                    'query' => [
                                        'range' => [
                                            'attributes.values.value_number' => [
                                                'gte' => 1337,
                                                'lte' => 2137,
                                                'boost' => 1.0,
                                            ],
                                        ],
                                    ],
                                ],
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

        $this
            ->actingAs($this->$user)
            ->json(
                'GET',
                '/products',
                [
                    'attribute_not' => [
                        $attribute->slug => [
                            'min' => 1337,
                        ],
                    ],
                ]
            )
            ->assertOk();

        $this->assertElasticQuery([
            'bool' => [
                'must' => [],
                'should' => [],
                'filter' => [
                    [
                        'terms' => [
                            'attributes_slug' => [
                                $attribute->slug,
                            ],
                            'boost' => 1.0,
                        ],
                    ],
                    [
                        'bool' => [
                            'must_not' => [
                                'nested' => [
                                    'path' => 'attributes.values',
                                    'query' => [
                                        'range' => [
                                            'attributes.values.value_number' => [
                                                'gte' => 1337,
                                                'boost' => 1.0,
                                            ],
                                        ],
                                    ],
                                ],
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

        $this
            ->actingAs($this->$user)
            ->json(
                'GET',
                '/products',
                [
                    'attribute_not' => [
                        $attribute->slug => [
                            'max' => 2137,
                        ],
                    ],
                ]
            )
            ->assertOk();

        $this->assertElasticQuery([
            'bool' => [
                'must' => [],
                'should' => [],
                'filter' => [
                    [
                        'terms' => [
                            'attributes_slug' => [
                                $attribute->slug,
                            ],
                            'boost' => 1.0,
                        ],
                    ],
                    [
                        'bool' => [
                            'must_not' => [
                                'nested' => [
                                    'path' => 'attributes.values',
                                    'query' => [
                                        'range' => [
                                            'attributes.values.value_number' => [
                                                'lte' => 2137,
                                                'boost' => 1.0,
                                            ],
                                        ],
                                    ],
                                ],
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
    public function testSearchByAttributeInvalidNumber($user): void
    {
        $this->$user->givePermissionTo('products.show');

        $attribute = Attribute::factory()->create([
            'sortable' => 1,
            'type' => 'number',
        ]);

        $this
            ->actingAs($this->$user)
            ->json(
                'GET',
                '/products',
                [
                    'attribute' => [
                        $attribute->slug => [
                            'min' => '2022-01-01',
                        ],
                    ],
                ]
            )
            ->assertUnprocessable();
    }

    /**
     * @dataProvider authProvider
     */
    public function testSearchByAttributeDate($user): void
    {
        $this->$user->givePermissionTo('products.show');

        $attribute = Attribute::factory()->create([
            'sortable' => 1,
            'type' => 'date',
        ]);

        $this
            ->actingAs($this->$user)
            ->json(
                'GET',
                '/products',
                [
                    'attribute' => [
                        $attribute->slug => [
                            'min' => '2020-01-01',
                            'max' => '2022-01-01',
                        ],
                    ],
                ]
            )
            ->assertOk();

        $this->assertElasticQuery([
            'bool' => [
                'must' => [],
                'should' => [],
                'filter' => [
                    [
                        'terms' => [
                            'attributes_slug' => [
                                $attribute->slug,
                            ],
                            'boost' => 1.0,
                        ],
                    ],
                    [
                        'nested' => [
                            'path' => 'attributes.values',
                            'query' => [
                                'range' => [
                                    'attributes.values.value_date' => [
                                        'gte' => '2020-01-01',
                                        'lte' => '2022-01-01',
                                        'boost' => 1.0,
                                    ],
                                ],
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

        $this
            ->actingAs($this->$user)
            ->json(
                'GET',
                '/products',
                [
                    'attribute' => [
                        $attribute->slug => [
                            'min' => '2020-01-01',
                        ],
                    ],
                ]
            )
            ->assertOk();

        $this->assertElasticQuery([
            'bool' => [
                'must' => [],
                'should' => [],
                'filter' => [
                    [
                        'terms' => [
                            'attributes_slug' => [
                                $attribute->slug,
                            ],
                            'boost' => 1.0,
                        ],
                    ],
                    [
                        'nested' => [
                            'path' => 'attributes.values',
                            'query' => [
                                'range' => [
                                    'attributes.values.value_date' => [
                                        'gte' => '2020-01-01',
                                        'boost' => 1.0,
                                    ],
                                ],
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

        $this
            ->actingAs($this->$user)
            ->json(
                'GET',
                '/products',
                [
                    'attribute' => [
                        $attribute->slug => [
                            'max' => '2022-01-01',
                        ],
                    ],
                ]
            )
            ->assertOk();

        $this->assertElasticQuery([
            'bool' => [
                'must' => [],
                'should' => [],
                'filter' => [
                    [
                        'terms' => [
                            'attributes_slug' => [
                                $attribute->slug,
                            ],
                            'boost' => 1.0,
                        ],
                    ],
                    [
                        'nested' => [
                            'path' => 'attributes.values',
                            'query' => [
                                'range' => [
                                    'attributes.values.value_date' => [
                                        'lte' => '2022-01-01',
                                        'boost' => 1.0,
                                    ],
                                ],
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
    public function testSearchByAttributeDateNegation($user): void
    {
        $this->$user->givePermissionTo('products.show');

        $attribute = Attribute::factory()->create([
            'sortable' => 1,
            'type' => 'date',
        ]);

        $this
            ->actingAs($this->$user)
            ->json(
                'GET',
                '/products',
                [
                    'attribute_not' => [
                        $attribute->slug => [
                            'min' => '2020-01-01',
                            'max' => '2022-01-01',
                        ],
                    ],
                ]
            )
            ->assertOk();

        $this->assertElasticQuery([
            'bool' => [
                'must' => [],
                'should' => [],
                'filter' => [
                    [
                        'terms' => [
                            'attributes_slug' => [
                                $attribute->slug,
                            ],
                            'boost' => 1.0,
                        ],
                    ],
                    [
                        'bool' => [
                            'must_not' => [
                                'nested' => [
                                    'path' => 'attributes.values',
                                    'query' => [
                                        'range' => [
                                            'attributes.values.value_date' => [
                                                'gte' => '2020-01-01',
                                                'lte' => '2022-01-01',
                                                'boost' => 1.0,
                                            ],
                                        ],
                                    ],
                                ],
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

        $this
            ->actingAs($this->$user)
            ->json(
                'GET',
                '/products',
                [
                    'attribute_not' => [
                        $attribute->slug => [
                            'min' => '2020-01-01',
                        ],
                    ],
                ]
            )
            ->assertOk();

        $this->assertElasticQuery([
            'bool' => [
                'must' => [],
                'should' => [],
                'filter' => [
                    [
                        'terms' => [
                            'attributes_slug' => [
                                $attribute->slug,
                            ],
                            'boost' => 1.0,
                        ],
                    ],
                    [
                        'bool' => [
                            'must_not' => [
                                'nested' => [
                                    'path' => 'attributes.values',
                                    'query' => [
                                        'range' => [
                                            'attributes.values.value_date' => [
                                                'gte' => '2020-01-01',
                                                'boost' => 1.0,
                                            ],
                                        ],
                                    ],
                                ],
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

        $this
            ->actingAs($this->$user)
            ->json(
                'GET',
                '/products',
                [
                    'attribute_not' => [
                        $attribute->slug => [
                            'max' => '2022-01-01',
                        ],
                    ],
                ]
            )
            ->assertOk();

        $this->assertElasticQuery([
            'bool' => [
                'must' => [],
                'should' => [],
                'filter' => [
                    [
                        'terms' => [
                            'attributes_slug' => [
                                $attribute->slug,
                            ],
                            'boost' => 1.0,
                        ],
                    ],
                    [
                        'bool' => [
                            'must_not' => [
                                'nested' => [
                                    'path' => 'attributes.values',
                                    'query' => [
                                        'range' => [
                                            'attributes.values.value_date' => [
                                                'lte' => '2022-01-01',
                                                'boost' => 1.0,
                                            ],
                                        ],
                                    ],
                                ],
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
    public function testSearchByAttributeInvalidDate($user): void
    {
        $this->$user->givePermissionTo('products.show');

        $attribute = Attribute::factory()->create([
            'sortable' => 1,
            'type' => 'date',
        ]);

        $this
            ->actingAs($this->$user)
            ->json(
                'GET',
                '/products',
                [
                    'attribute' => [
                        $attribute->slug => [
                            'min' => 1337,
                        ],
                    ],
                ]
            )
            ->assertUnprocessable();
    }

    /**
     * @dataProvider authProvider
     */
    public function testSortBySet($user): void
    {
        $this->$user->givePermissionTo('products.show');

        $this
            ->actingAs($this->$user)
            ->json(
                'GET',
                '/products',
                ['sort' => 'set.test:desc'],
            )
            ->assertOk();

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
                    ],
                ],
            ],
            24,
            [
                'sort' => [
                    [
                        'set.test' => 'desc',
                    ],
                ],
            ],
        );
    }

    /**
     * @dataProvider authProvider
     */
    public function testSortByCover($user): void
    {
        $this->$user->givePermissionTo('products.show');

        $this
            ->actingAs($this->$user)
            ->json(
                'GET',
                '/products',
                ['has_cover' => true],
            )
            ->assertOk();

        $this->assertElasticQuery(
            [
                'bool' => [
                    'must' => [],
                    'should' => [],
                    'filter' => [
                        [
                            'exists' => [
                                'field' => 'cover',
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
            ],
        );

        $this
            ->actingAs($this->$user)
            ->json(
                'GET',
                '/products',
                ['has_cover' => false],
            )
            ->assertOk();

        $this->assertElasticQuery(
            [
                'bool' => [
                    'must' => [],
                    'should' => [],
                    'filter' => [
                        [
                            'bool' => [
                                'must_not' => [
                                    'exists' => [
                                        'field' => 'cover',
                                    ],
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
            ],
        );
    }
}
