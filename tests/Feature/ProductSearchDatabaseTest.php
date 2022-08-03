<?php

namespace Tests\Feature;

use App\Models\Attribute;
use App\Models\AttributeOption;
use App\Models\Media;
use App\Models\Product;
use App\Models\ProductSet;
use App\Models\Tag;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Carbon;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class ProductSearchDatabaseTest extends TestCase
{
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

        $product = Product::factory()->create([
            'public' => true,
        ]);

        $this
            ->actingAs($this->$user)
            ->json('GET', '/products', ['limit' => 100])
            ->assertOk()
            ->assertJsonCount(1, 'data') // Should show only public products.
            ->assertJsonFragment([
                'id' => $product->getKey(),
            ]);

        $this->assertQueryCountLessThan(20);
    }

    /**
     * @dataProvider authProvider
     */
    public function testIndexHidden($user): void
    {
        $this->$user->givePermissionTo(['products.show', 'products.show_hidden']);

        $product1 = Product::factory()->create([
            'public' => true,
        ]);
        $set = ProductSet::factory()->create([
            'public' => true,
            'hide_on_index' => true,
        ]);
        $product1->sets()->sync([$set->getKey()]);

        $product2 = Product::factory()->create([
            'public' => false,
        ]);

        $this
            ->actingAs($this->$user)
            ->json('GET', '/products')
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonFragment(['id' => $product1->getKey()])
            ->assertJsonFragment(['id' => $product2->getKey()]);
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
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonFragment(['id' => $product->getKey()]);
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
            ->assertOk()
            ->assertJsonCount(2, 'data');
    }

    /**
     * @dataProvider trueBooleanProvider
     */
    public function testSearchByPublic($user, $boolean): void
    {
        $this->$user->givePermissionTo('products.show');

        $product = Product::factory()->create([
            'public' => true,
        ]);

        $this
            ->actingAs($this->$user)
            ->json('GET', '/products', ['public' => $boolean])
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonFragment(['id' => $product->getKey()]);
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
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonFragment(['id' => $product->getKey()]);
    }

    /**
     * @dataProvider authProvider
     */
    public function testSearchBySetNegation($user): void
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
            ->json('GET', '/products', ['sets_not' => [$set->slug]])
            ->assertOk()
            ->assertJsonMissing(['id' => $product->getKey()]);
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
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonFragment(['id' => $product->getKey()])
            ->assertJsonFragment(['id' => $product2->getKey()]);
    }

    /**
     * @dataProvider authProvider
     */
    public function testSearchBySetsNegation($user): void
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
                'sets_not' => [$set->slug, $set2->slug],
            ])
            ->assertOk()
            ->assertJsonMissing(['id' => $product->getKey()])
            ->assertJsonMissing(['id' => $product2->getKey()]);
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
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonFragment(['id' => $product->getKey()]);
    }

    /**
     * @dataProvider authProvider
     */
//    public function testSearchByParentSet($user): void
//    {
//        $this->$user->givePermissionTo('products.show');
//
//        $this
//            ->getProductsByParentSet($this->$user, true, $product)
//            ->assertOk()
//            ->assertJsonCount(1, 'data')
//            ->assertJsonFragment(['id' => $product->getKey()]);
//    }
    /**
     * @dataProvider authProvider
     */
    public function testSearchByParentSetWithPrivateChildUnauthorized($user): void
    {
        $this->$user->givePermissionTo('products.show');

        $this
            ->getProductsByParentSet($this->$user, false)
            ->assertOk()
            ->assertJsonCount(0, 'data');
    }

    /**
     * @dataProvider authProvider
     */
//    public function testSearchByParentSetWithPrivateChild($user): void
//    {
//        $this->$user->givePermissionTo([
//            'products.show',
//            'product_sets.show_hidden',
//        ]);
//
//        $this
//            ->getProductsByParentSet($this->$user, false, $product)
//            ->assertOk()
//            ->assertJsonCount(1, 'data')
//            ->assertJsonFragment(['id' => $product->getKey()]);
//    }

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
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonFragment(['id' => $product->getKey()]);
    }
    /**
     * @dataProvider authProvider
     */
    public function testSearchByTagNegation($user): void
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
            ->json('GET', '/products', ['tags_not' => [$tag->getKey()]])
            ->assertOk()
            ->assertJsonMissing(['id' => $product->getKey()]);
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
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonFragment(['id' => $product1->getKey()])
            ->assertJsonFragment(['id' => $product2->getKey()]);
    }

    /**
     * @dataProvider authProvider
     */
    public function testSearchByTagsNot($user): void
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
                'tags_not' => [
                    $tag1->getKey(),
                    $tag2->getKey(),
                ],
            ])
            ->assertOk()
            ->assertJsonMissing(['id' => $product1->getKey()])
            ->assertJsonMissing(['id' => $product2->getKey()]);
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

        $product = Product::factory()->create([
            'public' => true,
            'price_min' => 100,
            'price_max' => 200,
        ]);

        Product::factory()->create([
            'public' => true,
            'price_min' => 300,
            'price_max' => 1000,
        ]);

        Product::factory()->create([
            'public' => true,
            'price_min' => 10,
            'price_max' => 10,
        ]);

        $this
            ->actingAs($this->$user)
            ->json('GET', '/products', ['price' => ['min' => 100, 'max' => 200]])
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonFragment(['id' => $product->getKey()]);
    }

    /**
     * @dataProvider authProvider
     */
    public function testSearchByPhoto($user): void
    {
        $this->$user->givePermissionTo('products.show');

        $productNoPhoto = Product::factory()->create([
            'public' => true,
        ]);

        $productPhoto = Product::factory()->create([
            'public' => true,
        ]);

        $media = Media::factory()->create([
            'url' => 'https://picsum.photos/seed/' . rand(0, 999999) . '/800',
        ]);

        $productPhoto->media()->sync($media);

        $this
            ->actingAs($this->$user)
            ->json('GET', '/products', ['has_cover' => false])
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonFragment(['id' => $productNoPhoto->getKey()]);

        $this
            ->actingAs($this->$user)
            ->json('GET', '/products', ['has_cover' => true])
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonFragment(['id' => $productPhoto->getKey()]);
    }

    /**
     * @dataProvider authProvider
     */
    public function testSearchByAttributeId($user): void
    {
        $this->$user->givePermissionTo('products.show');

        $products = Product::factory()->count(2)->create([
            'public' => true,
        ]);

        $attribute = Attribute::factory()->create([
            'name' => 'Serie',
            'slug' => 'serie',
            'sortable' => 1,
            'type' => 'multi-choice-option',
        ]);

        $options = AttributeOption::factory()->count(2)->create([
            'attribute_id' => $attribute->getKey(),
            'index' => 1,
        ]);

        $products[0]->attributes()->attach($attribute->getKey());
        $products[0]->attributes->first()->pivot->options()->attach($options[0]->getKey());

        $products[1]->attributes()->attach($attribute->getKey());
        $products[1]->attributes->first()->pivot->options()->attach($options[1]->getKey());

        $this
            ->actingAs($this->$user)
            ->json(
                'GET',
                '/products',
                [
                    'attribute' => [
                        $attribute->slug => $options[0]->getKey(),
                    ],
                ]
            )
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonFragment([
                'name' => $attribute->name,
            ])
            ->assertJsonFragment([
                'id' => $options[0]->getKey(),
                'name' => $options[0]->name,
            ])
            ->assertJsonMissing([
                'id' => $options[1]->getKey(),
                'name' => $options[1]->name,
            ]);
    }

    /**
     * @dataProvider authProvider
     */
    public function testSearchByAttributeNumber($user): void
    {
        $this->$user->givePermissionTo('products.show');

        $products = Product::factory()->count(2)->create([
            'public' => true,
        ]);

        $attribute = Attribute::factory()->create([
            'name' => 'Ilość stron',
            'slug' => 'ilosc-stron',
            'sortable' => 1,
            'type' => 'number',
        ]);

        $option1 = AttributeOption::factory()->create([
            'attribute_id' => $attribute->getKey(),
            'index' => 1,
            'value_number' => 1437,
        ]);

        $option2 = AttributeOption::factory()->create([
            'attribute_id' => $attribute->getKey(),
            'index' => 1,
            'value_number' => 2237,
        ]);

        $products[0]->attributes()->attach($attribute->getKey());
        $products[0]->attributes->first()->pivot->options()->attach($option1->getKey());

        $products[1]->attributes()->attach($attribute->getKey());
        $products[1]->attributes->first()->pivot->options()->attach($option2->getKey());

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
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonFragment([
                'name' => $attribute->name,
            ])
            ->assertJsonFragment([
                'id' => $option1->getKey(),
                'value_number' => $option1->value_number,
            ])
            ->assertJsonMissing([
                'id' => $option2->getKey(),
                'value_number' => $option2->value_number,
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
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonFragment([
                'name' => $attribute->name,
            ])
            ->assertJsonFragment([
                'id' => $option1->getKey(),
                'value_number' => $option1->value_number,
            ])
            ->assertJsonFragment([
                'id' => $option2->getKey(),
                'value_number' => $option2->value_number,
            ]);

        $this
            ->actingAs($this->$user)
            ->json(
                'GET',
                '/products',
                [
                    'attribute' => [
                        $attribute->slug => [
                            'min' => 2337,
                        ],
                    ],
                ]
            )
            ->assertOk()
            ->assertJsonCount(0, 'data');

        $this
            ->actingAs($this->$user)
            ->json(
                'GET',
                '/products',
                [
                    'attribute' => [
                        $attribute->slug => [
                            'max' => 1337,
                        ],
                    ],
                ]
            )
            ->assertOk()
            ->assertJsonCount(0, 'data');
    }

    /**
     * @dataProvider authProvider
     */
    public function testSearchByAttributeDate($user): void
    {
        $this->$user->givePermissionTo('products.show');

        $products = Product::factory()->count(2)->create([
            'public' => true,
        ]);

        $attribute = Attribute::factory()->create([
            'name' => 'Data wydania',
            'slug' => 'data-wydania',
            'sortable' => 1,
            'type' => 'date',
        ]);

        $option1 = AttributeOption::factory()->create([
            'attribute_id' => $attribute->getKey(),
            'index' => 1,
            'value_date' => '2022-09-11',
        ]);

        $option2 = AttributeOption::factory()->create([
            'attribute_id' => $attribute->getKey(),
            'index' => 1,
            'value_date' => '2022-11-11',
        ]);

        $products[0]->attributes()->attach($attribute->getKey());
        $products[0]->attributes->first()->pivot->options()->attach($option1->getKey());

        $products[1]->attributes()->attach($attribute->getKey());
        $products[1]->attributes->first()->pivot->options()->attach($option2->getKey());

        $this
            ->actingAs($this->$user)
            ->json(
                'GET',
                '/products',
                [
                    'attribute' => [
                        $attribute->slug => [
                            'min' => '2022-09-01',
                            'max' => '2022-09-30',
                        ],
                    ],
                ]
            )
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonFragment([
                'name' => $attribute->name,
            ])
            ->assertJsonFragment([
                'id' => $option1->getKey(),
                'value_date' => $option1->value_date,
            ])
            ->assertJsonMissing([
                'id' => $option2->getKey(),
                'value_date' => $option2->value_date,
            ]);

        $this
            ->actingAs($this->$user)
            ->json(
                'GET',
                '/products',
                [
                    'attribute' => [
                        $attribute->slug => [
                            'min' => '2022-09-01',
                            'max' => '2022-11-30',
                        ],
                    ],
                ]
            )
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonFragment([
                'name' => $attribute->name,
            ])
            ->assertJsonFragment([
                'id' => $option1->getKey(),
                'value_date' => $option1->value_date,
            ])
            ->assertJsonFragment([
                'id' => $option2->getKey(),
                'value_date' => $option2->value_date,
            ]);

        $this
            ->actingAs($this->$user)
            ->json(
                'GET',
                '/products',
                [
                    'attribute' => [
                        $attribute->slug => [
                            'min' => '2022-12-01',
                        ],
                    ],
                ]
            )
            ->assertOk()
            ->assertJsonCount(0, 'data');

        $this
            ->actingAs($this->$user)
            ->json(
                'GET',
                '/products',
                [
                    'attribute' => [
                        $attribute->slug => [
                            'max' => '2022-09-10',
                        ],
                    ],
                ]
            )
            ->assertOk()
            ->assertJsonCount(0, 'data');
    }

    /**
     * @dataProvider authProvider
     */
    public function testSearchByAttributeNotId($user): void
    {
        $this->$user->givePermissionTo('products.show');

        $products = Product::factory()->count(2)->create([
            'public' => true,
        ]);

        $attribute = Attribute::factory()->create([
            'name' => 'Serie',
            'slug' => 'serie',
            'sortable' => 1,
            'type' => 'multi-choice-option',
        ]);

        $options = AttributeOption::factory()->count(2)->create([
            'attribute_id' => $attribute->getKey(),
            'index' => 1,
        ]);

        $products[0]->attributes()->attach($attribute->getKey());
        $products[0]->attributes->first()->pivot->options()->attach($options[0]->getKey());

        $products[1]->attributes()->attach($attribute->getKey());
        $products[1]->attributes->first()->pivot->options()->attach($options[1]->getKey());

        $this
            ->actingAs($this->$user)
            ->json(
                'GET',
                '/products',
                [
                    'attribute_not' => [
                        $attribute->slug => $options[0]->getKey(),
                    ],
                ]
            )
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonMissing([
                'id' => $products[0]->getKey(),
            ])
            ->assertJsonFragment([
                'id' => $products[1]->getKey(),
            ]);
    }

    /**
     * @dataProvider authProvider
     */
    public function testSearchByAttributeNotNumber($user): void
    {
        $this->$user->givePermissionTo('products.show');

        $products = Product::factory()->count(2)->create([
            'public' => true,
        ]);

        $attribute = Attribute::factory()->create([
            'name' => 'Ilość stron',
            'slug' => 'ilosc-stron',
            'sortable' => 1,
            'type' => 'number',
        ]);

        $option1 = AttributeOption::factory()->create([
            'attribute_id' => $attribute->getKey(),
            'index' => 1,
            'value_number' => 1437,
        ]);

        $option2 = AttributeOption::factory()->create([
            'attribute_id' => $attribute->getKey(),
            'index' => 1,
            'value_number' => 2237,
        ]);

        $products[0]->attributes()->attach($attribute->getKey());
        $products[0]->attributes->first()->pivot->options()->attach($option1->getKey());

        $products[1]->attributes()->attach($attribute->getKey());
        $products[1]->attributes->first()->pivot->options()->attach($option2->getKey());

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
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonMissing([
                'id' => $products[0]->getKey(),
            ])
            ->assertJsonFragment([
                'id' => $products[1]->getKey(),
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
            ->assertOk()
            ->assertJsonCount(0, 'data');

        $this
            ->actingAs($this->$user)
            ->json(
                'GET',
                '/products',
                [
                    'attribute_not' => [
                        $attribute->slug => [
                            'min' => 2337,
                        ],
                    ],
                ]
            )
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonFragment([
                'id' => $products[0]->getKey(),
            ])
            ->assertJsonFragment([
                'id' => $products[1]->getKey(),
            ]);

        $this
            ->actingAs($this->$user)
            ->json(
                'GET',
                '/products',
                [
                    'attribute_not' => [
                        $attribute->slug => [
                            'max' => 1337,
                        ],
                    ],
                ]
            )
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonFragment([
                'id' => $products[0]->getKey(),
            ])
            ->assertJsonFragment([
                'id' => $products[1]->getKey(),
            ]);
    }

    /**
     * @dataProvider authProvider
     */
    public function testSearchByAttributeNotDate($user): void
    {
        $this->$user->givePermissionTo('products.show');

        $products = Product::factory()->count(2)->create([
            'public' => true,
        ]);

        $attribute = Attribute::factory()->create([
            'name' => 'Data wydania',
            'slug' => 'data-wydania',
            'sortable' => 1,
            'type' => 'date',
        ]);

        $option1 = AttributeOption::factory()->create([
            'attribute_id' => $attribute->getKey(),
            'index' => 1,
            'value_date' => '2022-09-11',
        ]);

        $option2 = AttributeOption::factory()->create([
            'attribute_id' => $attribute->getKey(),
            'index' => 1,
            'value_date' => '2022-11-11',
        ]);

        $products[0]->attributes()->attach($attribute->getKey());
        $products[0]->attributes->first()->pivot->options()->attach($option1->getKey());

        $products[1]->attributes()->attach($attribute->getKey());
        $products[1]->attributes->first()->pivot->options()->attach($option2->getKey());

        $this
            ->actingAs($this->$user)
            ->json(
                'GET',
                '/products',
                [
                    'attribute_not' => [
                        $attribute->slug => [
                            'min' => '2022-09-01',
                            'max' => '2022-09-30',
                        ],
                    ],
                ]
            )
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonMissing([
                'id' => $products[0]->getKey(),
            ])
            ->assertJsonFragment([
                'id' => $products[1]->getKey(),
            ]);

        $this
            ->actingAs($this->$user)
            ->json(
                'GET',
                '/products',
                [
                    'attribute_not' => [
                        $attribute->slug => [
                            'min' => '2022-09-01',
                            'max' => '2022-11-30',
                        ],
                    ],
                ]
            )
            ->assertOk()
            ->assertJsonCount(0, 'data');

        $this
            ->actingAs($this->$user)
            ->json(
                'GET',
                '/products',
                [
                    'attribute_not' => [
                        $attribute->slug => [
                            'min' => '2022-12-01',
                        ],
                    ],
                ]
            )
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonFragment([
                'id' => $products[0]->getKey(),
            ])
            ->assertJsonFragment([
                'id' => $products[1]->getKey(),
            ]);

        $this
            ->actingAs($this->$user)
            ->json(
                'GET',
                '/products',
                [
                    'attribute_not' => [
                        $attribute->slug => [
                            'max' => '2022-09-10',
                        ],
                    ],
                ]
            )
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonFragment([
                'id' => $products[0]->getKey(),
            ])
            ->assertJsonFragment([
                'id' => $products[1]->getKey(),
            ]);
    }

    /**
     * @dataProvider authProvider
     */
    public function testSortBySetOrder($user): void
    {
        $this->$user->givePermissionTo('products.show');

        $product1 = Product::factory()->create([
            'public' => true,
        ]);
        $product2 = Product::factory()->create([
            'public' => true,
        ]);
        $product3 = Product::factory()->create([
            'public' => true,
        ]);
        $set = ProductSet::factory()->create([
            'slug' => 'test',
            'public' => true,
            'hide_on_index' => false,
        ]);
        $set->products()->sync([
            $product1->getKey() => ['order' => 1],
            $product2->getKey() => ['order' => 2],
            $product3->getKey() => ['order' => 3],
        ]);

        $response = $this
            ->actingAs($this->$user)
            ->json('GET', '/products', ['sort' => 'set.test', 'sets[]' => 'test']);
        $response
            ->assertOk()
            ->assertJsonCount(3, 'data');

        $data = $response->getData()->data;
        $this->assertEquals($product1->getKey(), $data[0]->id);
        $this->assertEquals($product2->getKey(), $data[1]->id);
        $this->assertEquals($product3->getKey(), $data[2]->id);

        $this->assertQueryCountLessThan(20);

        // desc
        $response = $this
            ->actingAs($this->$user)
            ->json('GET', '/products', ['sort' => 'set.test:desc', 'sets[]' => 'test']);

        $data = $response->getData()->data;
        $this->assertEquals($product1->getKey(), $data[2]->id);
        $this->assertEquals($product2->getKey(), $data[1]->id);
        $this->assertEquals($product3->getKey(), $data[0]->id);
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

        return $this
            ->actingAs($user)
            ->getJson("/products?sets[]={$parentSet->slug}");
    }
}
