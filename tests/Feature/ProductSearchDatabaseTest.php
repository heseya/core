<?php

namespace Tests\Feature;

use Domain\Price\Dtos\PriceDto;
use Domain\Price\Enums\ProductPriceType;
use App\Models\Media;
use App\Models\Product;
use App\Repositories\Contracts\ProductRepositoryContract;
use Brick\Math\Exception\NumberFormatException;
use Brick\Math\Exception\RoundingNecessaryException;
use Brick\Money\Exception\UnknownCurrencyException;
use Brick\Money\Money;
use Domain\Currency\Currency;
use Domain\ProductAttribute\Enums\AttributeType;
use Domain\ProductAttribute\Models\Attribute;
use Domain\ProductAttribute\Models\AttributeOption;
use Domain\ProductSet\ProductSet;
use Domain\SalesChannel\SalesChannelRepository;
use Domain\Tag\Models\Tag;
use Heseya\Dto\DtoException;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\App;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class ProductSearchDatabaseTest extends TestCase
{
    /**
     * @dataProvider authProvider
     */
    public function testIndex($user): void
    {
        $this->{$user}->givePermissionTo('products.show');

        $salesChannel = app(SalesChannelRepository::class)->getDefault();

        $product = Product::factory()->create([
            'public' => true,
        ]);
        $salesChannel->products()->attach($product);

        $set = ProductSet::factory()->create([
            'public' => true,
        ]);
        $product->sets()->sync([$set->getKey()]);

        $product = Product::factory()->create([
            'public' => true,
        ]);
        $salesChannel->products()->attach($product);

        $this
            ->actingAs($this->{$user})
            ->json('GET', '/products', ['limit' => 100])
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonFragment([
                'id' => $product->getKey(),
            ]);

        $this->assertQueryCountLessThan(22);
    }

    /**
     * @dataProvider authProvider
     */
    public function testSearch($user): void
    {
        $this->{$user}->givePermissionTo('products.show');

        $salesChannel = app(SalesChannelRepository::class)->getDefault();

        $product = Product::factory()->create([
            'public' => true,
        ]);
        $salesChannel->products()->attach($product);

        $this
            ->actingAs($this->{$user})
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
        $this->{$user}->givePermissionTo('products.show');

        $salesChannel = app(SalesChannelRepository::class)->getDefault();

        $firstProduct = Product::factory()->create([
            'public' => true,
        ]);
        $salesChannel->products()->attach($firstProduct);

        $secondProduct = Product::factory()->create([
            'public' => true,
            'created_at' => Carbon::now()->addHour(),
        ]);
        $salesChannel->products()->attach($secondProduct);

        // Dummy product to check if response will return only 2 products created above
        Product::factory()->create([
            'public' => true,
            'created_at' => Carbon::now()->addHour(),
        ]);

        $this
            ->actingAs($this->{$user})
            ->json('GET', '/products', [
                'ids' => [
                    $firstProduct->getKey(),
                    $secondProduct->getKey(),
                ],
            ])
            ->assertOk()
            ->assertJsonCount(2, 'data');
    }

    /**
     * @dataProvider authProvider
     */
    public function testSearchByPublic($user): void
    {
        $this->{$user}->givePermissionTo('products.show');

        $salesChannel = app(SalesChannelRepository::class)->getDefault();

        $product = Product::factory()->create([
            'public' => true,
        ]);
        $salesChannel->products()->attach($product);

        $this
            ->actingAs($this->{$user})
            ->json('GET', '/products', ['public' => true])
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonFragment(['id' => $product->getKey()]);
    }

    /**
     * @dataProvider authProvider
     */
    public function testSearchBySet($user): void
    {
        $this->{$user}->givePermissionTo('products.show');

        $salesChannel = app(SalesChannelRepository::class)->getDefault();

        $set = ProductSet::factory()->create([
            'public' => true,
        ]);

        $product = Product::factory()->create([
            'public' => true,
        ]);
        $salesChannel->products()->attach($product);

        // Product not in set
        $product2 = Product::factory()->create([
            'public' => true,
        ]);
        $salesChannel->products()->attach($product2);

        $set->products()->attach($product);

        $this
            ->actingAs($this->{$user})
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
        $this->{$user}->givePermissionTo('products.show');

        $salesChannel = app(SalesChannelRepository::class)->getDefault();

        $set = ProductSet::factory()->create([
            'public' => true,
        ]);

        $product = Product::factory()->create([
            'public' => true,
        ]);
        $salesChannel->products()->attach($product);

        // Product not in set
        $product2 = Product::factory()->create([
            'public' => true,
        ]);
        $salesChannel->products()->attach($product2);

        $set->products()->attach($product);

        $this
            ->actingAs($this->{$user})
            ->json('GET', '/products', ['sets_not' => [$set->slug]])
            ->assertOk()
            ->assertJsonMissing(['id' => $product->getKey()]);
    }

    /**
     * @dataProvider authProvider
     */
    public function testSearchBySets($user): void
    {
        $this->{$user}->givePermissionTo('products.show');

        $salesChannel = app(SalesChannelRepository::class)->getDefault();

        $set = ProductSet::factory()->create([
            'public' => true,
        ]);

        $set2 = ProductSet::factory()->create([
            'public' => true,
        ]);

        $product = Product::factory()->create([
            'public' => true,
        ]);
        $salesChannel->products()->attach($product);

        $product2 = Product::factory()->create([
            'public' => true,
        ]);
        $salesChannel->products()->attach($product2);

        // Product not in set
        $product3 =  Product::factory()->create([
            'public' => true,
        ]);
        $salesChannel->products()->attach($product3);

        $set->products()->attach($product);
        $set2->products()->attach($product2);

        $this
            ->actingAs($this->{$user})
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
        $this->{$user}->givePermissionTo('products.show');

        $salesChannel = app(SalesChannelRepository::class)->getDefault();

        $set = ProductSet::factory()->create([
            'public' => true,
        ]);

        $set2 = ProductSet::factory()->create([
            'public' => true,
        ]);

        $product = Product::factory()->create([
            'public' => true,
        ]);
        $salesChannel->products()->attach($product);

        $product2 = Product::factory()->create([
            'public' => true,
        ]);
        $salesChannel->products()->attach($product2);

        // Product not in set
        $product3 = Product::factory()->create([
            'public' => true,
        ]);
        $salesChannel->products()->attach($product3);

        $set->products()->attach($product);
        $set2->products()->attach($product2);

        $this
            ->actingAs($this->{$user})
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
        $this->{$user}->givePermissionTo('products.show');

        $set = ProductSet::factory()->create([
            'public' => false,
        ]);

        $this
            ->actingAs($this->{$user})
            ->json('GET', '/products', ['sets' => [$set->slug]])
            ->assertUnprocessable();
    }

    /**
     * @dataProvider authProvider
     */
    public function testSearchBySetHidden($user): void
    {
        $this->{$user}->givePermissionTo(['products.show', 'product_sets.show_hidden']);

        $salesChannel = app(SalesChannelRepository::class)->getDefault();

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
        $salesChannel->products()->attach($product);

        // Product not in set
        $product2 = Product::factory()->create([
            'public' => true,
        ]);
        $salesChannel->products()->attach($product2);

        $set->products()->attach($product);

        $this
            ->actingAs($this->{$user})
            ->json('GET', '/products', ['sets' => [$set->slug]])
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonFragment(['id' => $product->getKey()]);
    }

    /**
     * @dataProvider authProvider
     */
    public function testSearchByParentSet($user): void
    {
        $this->{$user}->givePermissionTo('products.show');

        $this
            ->getProductsByParentSet($this->{$user}, true, $product)
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonFragment(['id' => $product->getKey()]);
    }

    /**
     * @dataProvider authProvider
     */
    public function testSearchByGrandParentSet($user): void
    {
        $this->{$user}->givePermissionTo('products.show');

        $salesChannel = app(SalesChannelRepository::class)->getDefault();

        $grandParentSet = ProductSet::factory()->create([
            'public' => true,
        ]);

        $parentSet = ProductSet::factory()->create([
            'parent_id' => $grandParentSet->getKey(),
            'public' => true,
        ]);

        $childSet = ProductSet::factory()->create([
            'parent_id' => $parentSet->getKey(),
            'public' => true,
        ]);

        $productRef = Product::factory()->create([
            'public' => true,
        ]);
        $salesChannel->products()->attach($productRef);

        // Product not in set
        $product2 = Product::factory()->create([
            'public' => true,
        ]);
        $salesChannel->products()->attach($product2);

        $childSet->products()->attach($productRef);

        $this->actingAs($this->{$user})
            ->json('GET', '/products', ['sets' => [$grandParentSet->slug]])
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonFragment(['id' => $productRef->getKey()]);
        // + 1 additional query per nesting level
        $this->assertQueryCountLessThan(23);
    }

    /**
     * @dataProvider authProvider
     */
    public function testSearchByParentSetWithPrivateChildUnauthorized($user): void
    {
        $this->{$user}->givePermissionTo('products.show');

        $this
            ->getProductsByParentSet($this->{$user}, false)
            ->assertOk()
            ->assertJsonCount(0, 'data');
    }

    /**
     * @dataProvider authProvider
     */
    public function testSearchByParentSetWithPrivateChild($user): void
    {
        $this->{$user}->givePermissionTo([
            'products.show',
            'product_sets.show_hidden',
        ]);

        $this
            ->getProductsByParentSet($this->{$user}, false, $product)
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonFragment(['id' => $product->getKey()]);
    }

    /**
     * @dataProvider authProvider
     */
    public function testSearchByTag($user): void
    {
        $this->{$user}->givePermissionTo('products.show');

        $salesChannel = app(SalesChannelRepository::class)->getDefault();

        $tag = Tag::factory()->create();

        $product = Product::factory()->create([
            'public' => true,
        ]);
        $salesChannel->products()->attach($product);

        // Product not in tag
        $product2 = Product::factory()->create([
            'public' => true,
        ]);
        $salesChannel->products()->attach($product2);

        $tag->products()->attach($product);

        $this
            ->actingAs($this->{$user})
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
        $this->{$user}->givePermissionTo('products.show');

        $salesChannel = app(SalesChannelRepository::class)->getDefault();

        $tag = Tag::factory()->create();

        $product = Product::factory()->create([
            'public' => true,
        ]);
        $salesChannel->products()->attach($product);

        // Product not in tag
        $product2 = Product::factory()->create([
            'public' => true,
        ]);
        $salesChannel->products()->attach($product2);

        $tag->products()->attach($product);

        $this
            ->actingAs($this->{$user})
            ->json('GET', '/products', ['tags_not' => [$tag->getKey()]])
            ->assertOk()
            ->assertJsonMissing(['id' => $product->getKey()]);
    }

    /**
     * @dataProvider authProvider
     */
    public function testSearchByTags($user): void
    {
        $this->{$user}->givePermissionTo('products.show');

        $salesChannel = app(SalesChannelRepository::class)->getDefault();

        $tag1 = Tag::factory()->create();

        $product1 = Product::factory()->create([
            'public' => true,
        ]);
        $salesChannel->products()->attach($product1);

        $tag2 = Tag::factory()->create();

        $product2 = Product::factory()->create([
            'public' => true,
        ]);
        $salesChannel->products()->attach($product2);

        // Product not in tag
        $product3 = Product::factory()->create([
            'public' => true,
        ]);
        $salesChannel->products()->attach($product3);

        $tag1->products()->attach($product1);
        $tag2->products()->attach($product2);

        $this->actingAs($this->{$user})
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
        $this->{$user}->givePermissionTo('products.show');

        $salesChannel = app(SalesChannelRepository::class)->getDefault();

        $tag1 = Tag::factory()->create();

        $product1 = Product::factory()->create([
            'public' => true,
        ]);
        $salesChannel->products()->attach($product1);

        $tag2 = Tag::factory()->create();

        $product2 = Product::factory()->create([
            'public' => true,
        ]);
        $salesChannel->products()->attach($product2);

        // Product not in tag
        $product3 = Product::factory()->create([
            'public' => true,
        ]);
        $salesChannel->products()->attach($product3);

        $tag1->products()->attach($product1);
        $tag2->products()->attach($product2);

        $this->actingAs($this->{$user})
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
        $this->{$user}->givePermissionTo('products.show');

        $this
            ->actingAs($this->{$user})
            ->json('GET', '/products?metadata.erp_id=1000&metadata.sku=S001')
            ->assertOk();
    }

    /**
     * @dataProvider authProvider
     */
    public function testSearchByMetadataClassicArray($user): void
    {
        $this->{$user}->givePermissionTo('products.show');

        $this
            ->actingAs($this->{$user})
            ->json('GET', '/products?metadata[erp_id]=1000&metadata[sku]=S001')
            ->assertOk();
    }

    /**
     * @dataProvider authProvider
     */
    public function testSearchByMetadataPrivate($user): void
    {
        $this->{$user}->givePermissionTo(['products.show', 'products.show_metadata_private']);

        $this
            ->actingAs($this->{$user})
            ->json('GET', '/products?metadata_private.sku=S001')
            ->assertOk();
    }

    /**
     * @dataProvider authProvider
     */
    public function testSearchByMetadataPrivateUnauthorized($user): void
    {
        $this->{$user}->givePermissionTo('products.show');

        $this
            ->actingAs($this->{$user})
            ->json('GET', '/products?metadata_private.sku=S001')
            ->assertUnprocessable();
    }

    /**
     * @dataProvider authProvider
     *
     * @throws DtoException
     * @throws NumberFormatException
     * @throws RoundingNecessaryException
     * @throws UnknownCurrencyException
     */
    public function testSearchByPrice($user): void
    {
        $this->{$user}->givePermissionTo('products.show');

        $salesChannel = app(SalesChannelRepository::class)->getDefault();

        /** @var ProductRepositoryContract $productRepository */
        $productRepository = App::make(ProductRepositoryContract::class);
        $currency = Currency::DEFAULT;

        $product = Product::factory()->create([
            'public' => true,
        ]);
        $salesChannel->products()->attach($product);
        $productRepository->setProductPrices($product->getKey(), [
            ProductPriceType::PRICE_MIN->value => [PriceDto::from(Money::of(100, $currency->value))],
            ProductPriceType::PRICE_MAX->value => [PriceDto::from(Money::of(200, $currency->value))],
        ]);

        $product2 = Product::factory()->create([
            'public' => true,
        ]);
        $salesChannel->products()->attach($product2);
        $productRepository->setProductPrices($product2->getKey(), [
            ProductPriceType::PRICE_MIN->value => [PriceDto::from(Money::of(300, $currency->value))],
            ProductPriceType::PRICE_MAX->value => [PriceDto::from(Money::of(1000, $currency->value))],
        ]);

        $product3 = Product::factory()->create([
            'public' => true,
        ]);
        $salesChannel->products()->attach($product3);
        $productRepository->setProductPrices($product3->getKey(), [
            ProductPriceType::PRICE_MIN->value => [PriceDto::from(Money::of(10, $currency->value))],
            ProductPriceType::PRICE_MAX->value => [PriceDto::from(Money::of(10, $currency->value))],
        ]);

        $this
            ->actingAs($this->{$user})
            ->json('GET', '/products', ['price' => [
                'min' => '100.00',
                'max' => '200.00',
                'currency' => $currency->value,
            ]])
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonFragment(['id' => $product->getKey()]);
    }

    /**
     * @dataProvider authProvider
     */
    public function testSearchByPhoto($user): void
    {
        $this->{$user}->givePermissionTo('products.show');

        $salesChannel = app(SalesChannelRepository::class)->getDefault();

        $productNoPhoto = Product::factory()->create([
            'public' => true,
        ]);
        $salesChannel->products()->attach($productNoPhoto);

        $productPhoto = Product::factory()->create([
            'public' => true,
        ]);
        $salesChannel->products()->attach($productPhoto);

        $media = Media::factory()->create([
            'url' => 'https://picsum.photos/seed/' . mt_rand(0, 999999) . '/800',
        ]);

        $productPhoto->media()->sync($media);

        $this
            ->actingAs($this->{$user})
            ->json('GET', '/products', ['has_cover' => false])
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonFragment(['id' => $productNoPhoto->getKey()]);

        $this
            ->actingAs($this->{$user})
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
        $this->{$user}->givePermissionTo('products.show');

        $products = Product::factory()->count(2)->create([
            'public' => true,
        ]);

        $salesChannel = app(SalesChannelRepository::class)->getDefault();
        $salesChannel->products()->attach($products);

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
            ->actingAs($this->{$user})
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
    public function testSearchByAttributeMultippleId($user): void
    {
        $this->{$user}->givePermissionTo('products.show');

        $products = Product::factory()->count(2)->create([
            'public' => true,
        ]);

        $salesChannel = app(SalesChannelRepository::class)->getDefault();
        $salesChannel->products()->attach($products);

        /** @var Attribute $attribute */
        $attribute = Attribute::factory()->create([
            'name' => 'Serie',
            'slug' => 'serie',
            'sortable' => 1,
            'type' => 'multi-choice-option',
        ]);

        /** @var AttributeOption $option1 */
        $option1 = AttributeOption::factory()->create([
            'attribute_id' => $attribute->getKey(),
            'index' => 2,
        ]);

        /** @var AttributeOption $option2 */
        $option2 = AttributeOption::factory()->create([
            'attribute_id' => $attribute->getKey(),
            'index' => 1,
        ]);

        $products[0]->attributes()->attach($attribute->getKey());
        $products[0]->attributes->first()->pivot->options()->attach($option1->getKey());

        $products[1]->attributes()->attach($attribute->getKey());
        $products[1]->attributes->first()->pivot->options()->attach($option2->getKey());

        $this
            ->actingAs($this->{$user})
            ->json(
                'GET',
                '/products',
                [
                    'attribute' => [
                        $attribute->slug => [
                            $option1->getKey(),
                            $option2->getKey(),
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
                'name' => $option1->name,
            ])
            ->assertJsonFragment([
                'id' => $option2->getKey(),
                'name' => $option2->name,
            ]);
    }

    /**
     * @dataProvider authProvider
     */
    public function testSearchByAttributeNumber($user): void
    {
        $this->{$user}->givePermissionTo('products.show');

        $products = Product::factory()->count(2)->create([
            'public' => true,
        ]);

        $salesChannel = app(SalesChannelRepository::class)->getDefault();
        $salesChannel->products()->attach($products);

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
            ->actingAs($this->{$user})
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
            ->actingAs($this->{$user})
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
            ->actingAs($this->{$user})
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
            ->actingAs($this->{$user})
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
        $this->{$user}->givePermissionTo('products.show');

        $products = Product::factory()->count(2)->create([
            'public' => true,
        ]);

        $salesChannel = app(SalesChannelRepository::class)->getDefault();
        $salesChannel->products()->attach($products);

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
            ->actingAs($this->{$user})
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
            ->actingAs($this->{$user})
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
            ->actingAs($this->{$user})
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
            ->actingAs($this->{$user})
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
        $this->{$user}->givePermissionTo('products.show');

        $products = Product::factory()->count(2)->create([
            'public' => true,
        ]);

        $salesChannel = app(SalesChannelRepository::class)->getDefault();
        $salesChannel->products()->attach($products);

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
            ->actingAs($this->{$user})
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
        $this->{$user}->givePermissionTo('products.show');

        $products = Product::factory()->count(2)->create([
            'public' => true,
        ]);

        $salesChannel = app(SalesChannelRepository::class)->getDefault();
        $salesChannel->products()->attach($products);

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
            ->actingAs($this->{$user})
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
            ->actingAs($this->{$user})
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
            ->actingAs($this->{$user})
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
            ->actingAs($this->{$user})
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
        $this->{$user}->givePermissionTo('products.show');

        $products = Product::factory()->count(2)->create([
            'public' => true,
        ]);

        $salesChannel = app(SalesChannelRepository::class)->getDefault();
        $salesChannel->products()->attach($products);

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
            ->actingAs($this->{$user})
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
            ->actingAs($this->{$user})
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
            ->actingAs($this->{$user})
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
            ->actingAs($this->{$user})
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
        $this->{$user}->givePermissionTo('products.show');

        $salesChannel = app(SalesChannelRepository::class)->getDefault();

        $product1 = Product::factory()->create([
            'public' => true,
        ]);
        $salesChannel->products()->attach($product1);

        $product2 = Product::factory()->create([
            'public' => true,
        ]);
        $salesChannel->products()->attach($product2);

        $product3 = Product::factory()->create([
            'public' => true,
        ]);
        $salesChannel->products()->attach($product3);

        $set = ProductSet::factory()->create([
            'slug' => 'test',
            'public' => true,
        ]);
        $set->products()->sync([
            $product1->getKey() => ['order' => 1],
            $product2->getKey() => ['order' => 2],
            $product3->getKey() => ['order' => 3],
        ]);

        $response = $this
            ->actingAs($this->{$user})
            ->json('GET', '/products', ['sort' => 'set.test', 'sets[]' => 'test']);
        $response
            ->assertOk()
            ->assertJsonCount(3, 'data');

        $data = $response->getData()->data;
        $this->assertEquals($product1->getKey(), $data[0]->id);
        $this->assertEquals($product2->getKey(), $data[1]->id);
        $this->assertEquals($product3->getKey(), $data[2]->id);

        $this->assertQueryCountLessThan(27);

        // desc
        $response = $this
            ->actingAs($this->{$user})
            ->json('GET', '/products', ['sort' => 'set.test:desc', 'sets[]' => 'test']);

        $data = $response->getData()->data;
        $this->assertEquals($product1->getKey(), $data[2]->id);
        $this->assertEquals($product2->getKey(), $data[1]->id);
        $this->assertEquals($product3->getKey(), $data[0]->id);
    }

    /**
     * @dataProvider authProvider
     */
    public function testSortByAttribute(string $user): void
    {
        $this->{$user}->givePermissionTo('products.show');

        $products = Product::factory()->count(3)->create([
            'public' => true,
        ]);

        $defaultSalesChannel = app(SalesChannelRepository::class)->getDefault();
        $defaultSalesChannel->products()->sync($products);

        $set = ProductSet::factory()->create([
            'slug' => 'all-maritime',
            'public' => true,
        ]);
        $set->products()->sync([
            $products[0]->getKey() => ['order' => 1],
            $products[1]->getKey() => ['order' => 2],
            $products[2]->getKey() => ['order' => 3],
        ]);

        $attribute = Attribute::factory()->create([
            'name' => 'Data wydania',
            'slug' => 'data-wydania',
            'sortable' => true,
            'type' => AttributeType::DATE,
        ]);

        $option1 = AttributeOption::factory()->create([
            'attribute_id' => $attribute->getKey(),
            'index' => 1,
            'value_date' => '2022-01-01',
        ]);

        $option2 = AttributeOption::factory()->create([
            'attribute_id' => $attribute->getKey(),
            'index' => 1,
            'value_date' => '2023-02-01',
        ]);

        $option3 = AttributeOption::factory()->create([
            'attribute_id' => $attribute->getKey(),
            'index' => 1,
            'value_date' => '2022-12-12',
        ]);

        $products[0]->attributes()->attach($attribute->getKey());
        $products[0]->attributes->first()->pivot->options()->attach($option2->getKey());

        $products[1]->attributes()->attach($attribute->getKey());
        $products[1]->attributes->first()->pivot->options()->attach($option1->getKey());

        $products[2]->attributes()->attach($attribute->getKey());
        $products[2]->attributes->first()->pivot->options()->attach($option3->getKey());

        $response = $this
            ->actingAs($this->{$user})
            ->json('GET', '/products', ['sets[]' => $set->slug, 'sort' => "attribute.{$attribute->getKey()}"]);
        $response
            ->assertOk()
            ->assertJsonCount(3, 'data');

        $data = $response->getData()->data;
        $this->assertEquals($products[1]->getKey(), $data[0]->id); //01-01
        $this->assertEquals($products[2]->getKey(), $data[1]->id); //12-12
        $this->assertEquals($products[0]->getKey(), $data[2]->id); //23

        $this->assertQueryCountLessThan(27);

        // desc
        $response = $this
            ->actingAs($this->{$user})
            ->json('GET', '/products', ['sets[]' => $set->slug, 'sort' => "attribute.{$attribute->getKey()}:desc"]);

        $data = $response->getData()->data;
        $this->assertEquals($products[0]->getKey(), $data[0]->id); //23
        $this->assertEquals($products[2]->getKey(), $data[1]->id); //12-12
        $this->assertEquals($products[1]->getKey(), $data[2]->id); //01-01
    }

    private function getProductsByParentSet(
        Authenticatable $user,
        bool $isChildSetPublic,
        ?Product &$productRef = null,
    ): TestResponse {
        $defaultSalesChannel = app(SalesChannelRepository::class)->getDefault();

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
        $defaultSalesChannel->products()->attach($productRef);

        // Product not in set
        $product2 = Product::factory()->create([
            'public' => true,
        ]);
        $defaultSalesChannel->products()->attach($product2);

        $childSet->products()->attach($productRef);

        return $this
            ->actingAs($user)
            ->getJson("/products?sets[]={$parentSet->slug}");
    }
}
