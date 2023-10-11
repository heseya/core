<?php

namespace Tests\Feature\Products;

use App\Models\Product;
use Domain\ProductAttribute\Enums\AttributeType;
use Domain\ProductAttribute\Models\Attribute;
use Domain\ProductAttribute\Models\AttributeOption;
use Domain\ProductSet\ProductSet;
use Tests\TestCase;

class ProductSortTest extends TestCase
{
    public static function attributeProvider(): array
    {
        return [
            'as user single option' => [
                'user',
                AttributeType::SINGLE_OPTION,
                'name',
                [
                    'ccc',
                    'bbb',
                    'aaa',
                ],
            ],
            'as user multi option' => [
                'user',
                AttributeType::MULTI_CHOICE_OPTION,
                'name',
                [
                    'ccc',
                    'bbb',
                    'aaa',
                ],
            ],
            'as user number' => ['user', AttributeType::NUMBER, 'value_number', [3, 2, 1,],],
            'as user date' => [
                'user',
                AttributeType::DATE,
                'value_date',
                [
                    '2023-12-12',
                    '2023-05-05',
                    '2023-01-02',
                ],
            ],
            'as app single option' => [
                'application',
                AttributeType::SINGLE_OPTION,
                'name',
                [
                    'ccc',
                    'bbb',
                    'aaa',
                ],
            ],
            'as app multi option' => [
                'application',
                AttributeType::MULTI_CHOICE_OPTION,
                'name',
                [
                    'ccc',
                    'bbb',
                    'aaa',
                ],
            ],
            'as app number' => ['application', AttributeType::NUMBER, 'value_number', [3, 2, 1]],
            'as app date' => [
                'application',
                AttributeType::DATE,
                'value_date',
                [
                    '2023-12-12',
                    '2023-05-05',
                    '2023-01-02',
                ],
            ],
        ];
    }

    /**
     * @dataProvider authProvider
     */
    public function testSortByAttributeWithId(string $user): void
    {
        $this->{$user}->givePermissionTo('products.show');

        /** @var Attribute $attribute */
        $attribute = Attribute::factory()->create([
            'type' => AttributeType::SINGLE_OPTION,
        ]);

        $product1 = $this->createProductWithAttribute($attribute, '2023-12-12');
        $product2 = $this->createProductWithAttribute($attribute, '2023-05-05');
        $product3 = $this->createProductWithAttribute($attribute, '2023-01-02');

        $response = $this
            ->actingAs($this->{$user})
            ->json('GET', '/products', [
                'sort' => "attribute.{$attribute->getKey()}:desc",
            ]);

        $this->assertEquals($product1->getKey(), $response->json('data.0.id'));
        $this->assertEquals($product2->getKey(), $response->json('data.1.id'));
        $this->assertEquals($product3->getKey(), $response->json('data.2.id'));

        $response = $this
            ->actingAs($this->{$user})
            ->json('GET', '/products', [
                'sort' => "attribute.{$attribute->getKey()}:asc",
            ]);

        $this->assertEquals($product3->getKey(), $response->json('data.0.id'));
        $this->assertEquals($product2->getKey(), $response->json('data.1.id'));
        $this->assertEquals($product1->getKey(), $response->json('data.2.id'));
    }

    /**
     * @dataProvider attributeProvider
     */
    public function testSortByAttributeWithSlug(string $user, AttributeType $type, string $field, array $values): void
    {
        $this->{$user}->givePermissionTo('products.show');

        /** @var Attribute $attribute */
        $attribute = Attribute::factory()->create([
            'type' => $type,
        ]);

        $product1 = $this->createProductWithAttribute($attribute, $values[0], $field);
        $product2 = $this->createProductWithAttribute($attribute, $values[1], $field);
        $product3 = $this->createProductWithAttribute($attribute, $values[2], $field);

        $response = $this
            ->actingAs($this->{$user})
            ->json('GET', '/products', [
                'sort' => "attribute.{$attribute->slug}:desc",
            ]);

        $this->assertEquals($product1->getKey(), $response->json('data.0.id'));
        $this->assertEquals($product2->getKey(), $response->json('data.1.id'));
        $this->assertEquals($product3->getKey(), $response->json('data.2.id'));

        $response = $this
            ->actingAs($this->{$user})
            ->json('GET', '/products', [
                'sort' => "attribute.{$attribute->slug}:asc",
            ]);

        $this->assertEquals($product3->getKey(), $response->json('data.0.id'));
        $this->assertEquals($product2->getKey(), $response->json('data.1.id'));
        $this->assertEquals($product1->getKey(), $response->json('data.2.id'));
    }

    /**
     * @dataProvider authProvider
     */
    public function testSortByAttributeDate(string $user): void
    {
        $this->{$user}->givePermissionTo('products.show');

        /** @var Attribute $attribute */
        $attribute = Attribute::factory()->create([
            'type' => AttributeType::DATE->value,
        ]);

        $product1 = $this->createProductWithAttribute($attribute, '2023-12-12', 'value_date');
        $product2 = $this->createProductWithAttribute($attribute, '2023-05-05', 'value_date');
        $product3 = $this->createProductWithAttribute($attribute, '2023-01-02', 'value_date');

        $response = $this
            ->actingAs($this->{$user})
            ->json('GET', '/products', [
                'sort' => "attribute.{$attribute->slug}:desc",
            ]);

        $this->assertEquals($product1->getKey(), $response->json('data.0.id'));
        $this->assertEquals($product2->getKey(), $response->json('data.1.id'));
        $this->assertEquals($product3->getKey(), $response->json('data.2.id'));

        $response = $this
            ->actingAs($this->{$user})
            ->json('GET', '/products', [
                'sort' => "attribute.{$attribute->slug}:asc",
            ]);

        $this->assertEquals($product3->getKey(), $response->json('data.0.id'));
        $this->assertEquals($product2->getKey(), $response->json('data.1.id'));
        $this->assertEquals($product1->getKey(), $response->json('data.2.id'));
    }

    /**
     * @dataProvider authProvider
     */
    public function testSortProductBySets(string $user): void
    {
        $this->{$user}->givePermissionTo('products.show');

        $set1 = ProductSet::factory()->create([
            'id' => 'bbbbbbbb-56d6-4174-9840-39231e0c3f2f',
            'public' => true,
            'slug' => 'set-1',
        ]);

        $product1ForSet1 = Product::factory()->create(['name' => 'Product 1 For Set 1 Order 2', 'public' => true]);
        $product2ForSet1 = Product::factory()->create(['name' => 'Product 2 For Set 1 Order 1', 'public' => true]);
        $product3ForSet1 = Product::factory()->create(['name' => 'Product 3 For Set 1 Order 0', 'public' => true]);

        $set1->products()->attach($product1ForSet1->getKey(), ['order' => 2]);
        $set1->products()->attach($product2ForSet1->getKey(), ['order' => 1]);
        $set1->products()->attach($product3ForSet1->getKey(), ['order' => 0]);

        $set2 = ProductSet::factory()->create([
            'id' => 'aaaaaaaa-56d6-4174-9840-39231e0c3f2f',
            'public' => true,
            'slug' => 'set-2',
        ]);

        $product1ForSet2 = Product::factory()->create(['name' => 'Product 1 For Set 2 Order 1', 'public' => true]);
        $product2ForSet2 = Product::factory()->create(['name' => 'Product 2 For Set 2 Order 2', 'public' => true]);
        $product3ForSet2 = Product::factory()->create(['name' => 'Product 3 For Set 2 Order 0', 'public' => true]);

        $set2->products()->attach($product1ForSet2->getKey(), ['order' => 1]);
        $set2->products()->attach($product2ForSet2->getKey(), ['order' => 2]);
        $set2->products()->attach($product3ForSet2->getKey(), ['order' => 0]);

        $set3 = ProductSet::factory()->create([
            'id' => 'cccccccc-56d6-4174-9840-39231e0c3f2f',
            'public' => true,
            'slug' => 'set-3',
        ]);

        $product1ForSet3 = Product::factory()->create(['name' => 'Product 1 For Set 3 Order 1', 'public' => true]);
        $product2ForSet3 = Product::factory()->create(['name' => 'Product 2 For Set 3 Order 0', 'public' => true]);
        $product3ForSet3 = Product::factory()->create(['name' => 'Product 3 For Set 3 Order 2', 'public' => true]);

        $set3->products()->attach($product1ForSet3->getKey(), ['order' => 1]);
        $set3->products()->attach($product2ForSet3->getKey(), ['order' => 0]);
        $set3->products()->attach($product3ForSet3->getKey(), ['order' => 2]);

        $set1->children()->save($set2);
        $set2->children()->save($set3);

        $this
            ->actingAs($this->{$user})
            ->json('GET', '/products', [
                'sets' => [
                    'set-1',
                ],
                'sort' => 'set.set-1',
            ])
            ->assertJsonPath('data.0.id', $product3ForSet1->getKey())
            ->assertJsonPath('data.1.id', $product2ForSet1->getKey())
            ->assertJsonPath('data.2.id', $product1ForSet1->getKey())
            ->assertJsonPath('data.3.id', $product3ForSet2->getKey())
            ->assertJsonPath('data.4.id', $product1ForSet2->getKey())
            ->assertJsonPath('data.5.id', $product2ForSet2->getKey())
            ->assertJsonPath('data.6.id', $product2ForSet3->getKey())
            ->assertJsonPath('data.7.id', $product1ForSet3->getKey())
            ->assertJsonPath('data.8.id', $product3ForSet3->getKey());
    }

    private function createProductWithAttribute(
        Attribute $attribute,
        string $optionName,
        string $field = 'name',
    ): Product {
        /** @var AttributeOption $option */
        $option = AttributeOption::factory()->create([
            $field => $optionName,
            'attribute_id' => $attribute->getKey(),
            'index' => 0,
        ]);

        /** @var Product $product */
        $product = Product::factory()->create([
            'public' => true,
        ]);
        $product->attributes()->attach($attribute->getKey());
        $product->attributes()->get()->each(
            fn (Attribute $attribute) => $attribute->pivot->options()->attach($option->getKey()),
        );

        return $product;
    }
}
