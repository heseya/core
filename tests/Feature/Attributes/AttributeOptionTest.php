<?php

namespace Tests\Feature\Attributes;

use App\Models\Product;
use Domain\ProductAttribute\Models\Attribute;
use Domain\ProductAttribute\Models\AttributeOption;
use Domain\ProductSet\ProductSet;
use Tests\TestCase;

class AttributeOptionTest extends TestCase
{
    /**
     * @dataProvider authProvider
     */
    public function testSearch($user): void
    {
        $this->{$user}->givePermissionTo('attributes.show');

        $attribute = Attribute::factory()->create();
        $target = AttributeOption::factory()->create([
            'name' => 'searchtarget',
            'attribute_id' => $attribute->getKey(),
            'index' => 0,
        ]);

        AttributeOption::factory()->count(10)->create([
            'name' => 'test',
            'attribute_id' => $attribute->getKey(),
            'index' => 0,
        ]);

        $this
            ->actingAs($this->{$user})
            ->json('GET', "/attributes/id:{$attribute->getKey()}/options", [
                'search' => 'searchtarget',
            ])
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonFragment(['name' => $target->name]);
    }

    /**
     * @dataProvider authProvider
     */
    public function testSearchByIds($user): void
    {
        $this->{$user}->givePermissionTo('attributes.show');

        $attribute = Attribute::factory()->create();
        $target = AttributeOption::factory()->create([
            'name' => 'searchtarget',
            'attribute_id' => $attribute->getKey(),
            'index' => 0,
        ]);

        AttributeOption::factory()->count(10)->create([
            'name' => 'test',
            'attribute_id' => $attribute->getKey(),
            'index' => 0,
        ]);

        $this
            ->actingAs($this->{$user})
            ->json('GET', "/attributes/id:{$attribute->getKey()}/options", [
                'ids' => [
                    $target->getKey(),
                ],
            ])
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonFragment(['name' => $target->name]);
    }

    /**
     * @dataProvider authProvider
     */
    public function testSearchNoString($user): void
    {
        $this->{$user}->givePermissionTo('attributes.show');

        $attribute = Attribute::factory()->create();

        $this
            ->actingAs($this->{$user})
            ->json('GET', "/attributes/id:{$attribute->getKey()}/options", [
                'search' => null,
            ])
            ->assertUnprocessable();
    }

    /**
     * @dataProvider authProvider
     */
    public function testSearchByProductSetSlug($user): void
    {
        $this->{$user}->givePermissionTo('attributes.show');

        $parent = ProductSet::factory()->create([
            'public' => true,
            'slug' => 'parent',
        ]);
        $child = ProductSet::factory()->create([
            'public' => true,
            'parent_id' => $parent->getKey(),
            'slug' => 'child',
        ]);
        $subChild = ProductSet::factory()->create([
            'public' => true,
            'parent_id' => $child->getKey(),
            'slug' => 'subChild',
        ]);
        $hiddenChild = ProductSet::factory()->create([
            'public' => false,
            'parent_id' => $parent->getKey(),
            'slug' => 'hidden-child',
        ]);

        $set = ProductSet::factory()->create([
            'public' => true,
            'slug' => 'set',
        ]);

        $attribute = Attribute::factory()->create();

        /** @var AttributeOption $optionParent */
        $optionParent = AttributeOption::factory()->create([
            'name' => 'Parent',
            'attribute_id' => $attribute->getKey(),
            'index' => 0,
        ]);
        /** @var AttributeOption $optionChild */
        $optionChild = AttributeOption::factory()->create([
            'name' => 'Child',
            'attribute_id' => $attribute->getKey(),
            'index' => 1,
        ]);
        /** @var AttributeOption $optionSubChild */
        $optionSubChild = AttributeOption::factory()->create([
            'name' => 'SubChild',
            'attribute_id' => $attribute->getKey(),
            'index' => 2,
        ]);
        /** @var AttributeOption $optionSet */
        $optionSet = AttributeOption::factory()->create([
            'name' => 'Set',
            'attribute_id' => $attribute->getKey(),
            'index' => 3,
        ]);
        /** @var AttributeOption $optionHidden */
        $optionHidden = AttributeOption::factory()->create([
            'name' => 'Hidden',
            'attribute_id' => $attribute->getKey(),
            'index' => 4,
        ]);
        /** @var AttributeOption $optionHiddenChild */
        $optionHiddenChild = AttributeOption::factory()->create([
            'name' => 'Hidden Child',
            'attribute_id' => $attribute->getKey(),
            'index' => 4,
        ]);

        $productParent = Product::factory()->create([
            'public' => true,
        ]);
        $productParent->sets()->sync([$parent->getKey()]);
        $productParent->attributes()->attach($attribute->getKey());
        $productParent->attributes->first()->product_attribute_pivot->options()->attach($optionParent->getKey());

        $productChild = Product::factory()->create([
            'public' => true,
        ]);
        $productChild->sets()->sync([$child->getKey()]);
        $productChild->attributes()->attach($attribute->getKey());
        $productChild->attributes->first()->product_attribute_pivot->options()->attach($optionChild->getKey());

        $productSubChild = Product::factory()->create([
            'public' => true,
        ]);
        $productSubChild->sets()->sync([$subChild->getKey()]);
        $productSubChild->attributes()->attach($attribute->getKey());
        $productSubChild->attributes->first()->product_attribute_pivot->options()->attach($optionSubChild->getKey());

        $product = Product::factory()->create([
            'public' => true,
        ]);
        $product->sets()->sync([$set->getKey()]);
        $product->attributes()->attach($attribute->getKey());
        $product->attributes->first()->product_attribute_pivot->options()->attach($optionSet->getKey());

        $productHidden = Product::factory()->create([
            'public' => false,
        ]);
        $productHidden->sets()->sync([$parent->getKey()]);
        $productHidden->attributes()->attach($attribute->getKey());
        $productHidden->attributes->first()->product_attribute_pivot->options()->attach($optionHidden->getKey());

        $productHiddenChild = Product::factory()->create([
            'public' => true,
        ]);
        $productHiddenChild->sets()->sync([$hiddenChild->getKey()]);
        $productHiddenChild->attributes()->attach($attribute->getKey());
        $productHiddenChild->attributes->first()->product_attribute_pivot->options()->attach($optionHiddenChild->getKey());

        $this
            ->actingAs($this->{$user})
            ->json('GET', "/attributes/id:{$attribute->getKey()}/options", [
                'product_set_slug' => $parent->slug,
            ])
            ->assertOk()
            ->assertJsonCount(3, 'data')
            ->assertJsonFragment(['name' => $optionParent->name])
            ->assertJsonFragment(['name' => $optionChild->name])
            ->assertJsonFragment(['name' => $optionSubChild->name])
            ->assertJsonMissing(['name' => $optionSet->name])
            ->assertJsonMissing(['name' => $optionHidden->name])
            ->assertJsonMissing(['name' => $optionHiddenChild->name]);

        $this
            ->actingAs($this->{$user})
            ->json('GET', "/attributes/id:{$attribute->getKey()}/options", [
                'product_set_slug' => $child->slug,
            ])
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonFragment(['name' => $optionChild->name])
            ->assertJsonFragment(['name' => $optionSubChild->name])
            ->assertJsonMissing(['name' => $optionParent->name])
            ->assertJsonMissing(['name' => $optionSet->name])
            ->assertJsonMissing(['name' => $optionHidden->name])
            ->assertJsonMissing(['name' => $optionHiddenChild->name]);
    }
}
