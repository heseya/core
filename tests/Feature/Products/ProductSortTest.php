<?php

namespace Tests\Feature\Products;

use App\Models\Product;
use Domain\ProductAttribute\Enums\AttributeType;
use Domain\ProductAttribute\Models\Attribute;
use Domain\ProductAttribute\Models\AttributeOption;
use Domain\SalesChannel\SalesChannelRepository;
use Tests\TestCase;

class ProductSortTest extends TestCase
{
    /**
     * @dataProvider authProvider
     */
    public function testSortByAttribute(string $user): void
    {
        $this->{$user}->givePermissionTo('products.show');

        $defaultSalesChannel = app(SalesChannelRepository::class)->getDefault();

        /** @var Attribute $attribute */
        $attribute = Attribute::factory()->create([
            'type' => AttributeType::SINGLE_OPTION,
        ]);

        $product1 = $this->createProductWithAttribute($attribute, '2023-12-12');
        $product2 = $this->createProductWithAttribute($attribute, '2023-05-05');
        $product3 = $this->createProductWithAttribute($attribute, '2023-01-02');

        $defaultSalesChannel->products()->sync([$product1->getKey(), $product2->getKey(), $product3->getKey()]);

        $response = $this
            ->actingAs($this->{$user})
            ->json('GET', '/products', [
                'sort' => "attribute.{$attribute->getKey()}:desc",
            ]);

        $this->assertEquals($product1->getKey(), $response->json('data.0.id'));
        $this->assertEquals($product2->getKey(), $response->json('data.1.id'));
        $this->assertEquals($product3->getKey(), $response->json('data.2.id'));
    }

    private function createProductWithAttribute(Attribute $attribute, string $optionName): Product
    {
        /** @var AttributeOption $option */
        $option = AttributeOption::factory()->create([
            'name' => $optionName,
            'attribute_id' => $attribute->getKey(),
            'index' => 0,
        ]);

        /** @var Product $product */
        $product = Product::factory()->create();
        $product->attributes()->attach($attribute->getKey());
        $product->attributes()->get()->each(
            fn (Attribute $attribute) => $attribute->pivot->options()->attach($option->getKey())
        );

        return $product;
    }
}
