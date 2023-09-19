<?php

namespace Tests\Feature\Products;

use App\Enums\AttributeType;
use App\Models\Attribute;
use App\Models\AttributeOption;
use App\Models\Product;
use App\Models\ProductSet;
use App\Models\Tag;
use App\Services\Contracts\ProductServiceContract;
use Tests\TestCase;

class ProductSearchValuesTest extends TestCase
{
    public Product $product;

    public function setUp(): void
    {
        parent::setUp();

        $this->product = Product::factory()->create([
            'name' => 'Searched product',
            'public' => true,
            'description_html' => 'Lorem ipsum',
            'description_short' => 'short',
        ]);
    }

    /**
     * @dataProvider authProvider
     */
    public function testUpdateTagSearchValues(string $user): void
    {
        $this->{$user}->givePermissionTo('tags.edit');

        $tag = Tag::factory()->create();
        $this->product->tags()->sync($tag->getKey());

        $this
            ->actingAs($this->{$user})
            ->json('PATCH', 'tags/id:' . $tag->getKey(), [
                'name' => 'Tag updated',
            ])
            ->assertOk();

        $this->assertDatabaseHas('products', [
            'id' => $this->product->getKey(),
            'name' => 'Searched product',
            'description_html' => 'Lorem ipsum',
            'search_values' => 'Tag updated',
        ]);
    }

    /**
     * @dataProvider authProvider
     */
    public function testDeleteTagSearchValues(string $user): void
    {
        $this->{$user}->givePermissionTo('tags.remove');

        $tag = Tag::factory()->create();
        $this->product->tags()->sync($tag->getKey());

        $this
            ->actingAs($this->{$user})
            ->json('DELETE', 'tags/id:' . $tag->getKey())
            ->assertNoContent();

        $this->assertDatabaseHas('products', [
            'id' => $this->product->getKey(),
            'name' => 'Searched product',
            'description_html' => 'Lorem ipsum',
            'search_values' => '',
        ]);
    }

    /**
     * @dataProvider authProvider
     */
    public function testUpdateProductSetSearchValues(string $user): void
    {
        $this->{$user}->givePermissionTo('product_sets.edit');

        $set = ProductSet::factory()->create([
            'public' => true,
        ]);
        $this->product->sets()->sync($set->getKey());

        $this
            ->actingAs($this->{$user})
            ->json('PATCH', 'product-sets/id:' . $set->getKey(), [
                'name' => 'set name',
                'parent_id' => null,
                'children_ids' => [],
            ])
            ->assertOk();

        $this->assertDatabaseHas('products', [
            'id' => $this->product->getKey(),
            'name' => 'Searched product',
            'description_html' => 'Lorem ipsum',
            'search_values' => 'set name',
        ]);
    }

    /**
     * @dataProvider authProvider
     */
    public function testDeleteProductSetSearchValues(string $user): void
    {
        $this->{$user}->givePermissionTo('product_sets.remove');

        $set = ProductSet::factory()->create([
            'public' => true,
        ]);
        $this->product->sets()->sync($set->getKey());

        $this
            ->actingAs($this->{$user})
            ->json('DELETE', 'product-sets/id:' . $set->getKey())
            ->assertNoContent();

        $this->assertDatabaseHas('products', [
            'id' => $this->product->getKey(),
            'name' => 'Searched product',
            'description_html' => 'Lorem ipsum',
            'search_values' => '',
        ]);
    }

    /**
     * @dataProvider authProvider
     */
    public function testDeleteParentProductSetSearchValues(string $user): void
    {
        $this->{$user}->givePermissionTo('product_sets.remove');

        $parent = ProductSet::factory()->create([
            'name' => 'Parent set',
        ]);

        $set = ProductSet::factory()->create([
            'public' => true,
            'name' => 'children set',
            'parent_id' => $parent->getKey(),
        ]);
        $this->product->sets()->sync([$parent->getKey(), $set->getKey()]);

        app(ProductServiceContract::class)->updateProductsSearchValues([$this->product->getKey()]);

        $this->assertDatabaseHas('products', [
            'id' => $this->product->getKey(),
            'name' => 'Searched product',
            'description_html' => 'Lorem ipsum',
            'search_values' => 'Parent set children set',
        ]);

        $this
            ->actingAs($this->{$user})
            ->json('DELETE', 'product-sets/id:' . $set->getKey())
            ->assertNoContent();

        $this->assertDatabaseHas('products', [
            'id' => $this->product->getKey(),
            'name' => 'Searched product',
            'description_html' => 'Lorem ipsum',
            'search_values' => 'Parent set',
        ]);
    }

    /**
     * @dataProvider authProvider
     */
    public function testUpdateAttributeSearchValues(string $user): void
    {
        $this->{$user}->givePermissionTo('attributes.edit');

        /** @var Attribute $attribute */
        $attribute = Attribute::factory()->create();
        $this->product->attributes()->attach($attribute->getKey());

        $this
            ->actingAs($this->{$user})
            ->json('PATCH', 'attributes/id:' . $attribute->getKey(), [
                'name' => 'updated attribute',
                'slug' => $attribute->slug,
                'type' => $attribute->type,
                'global' => true,
                'sortable' => true,
            ])
            ->assertOk();

        $this->assertDatabaseHas('products', [
            'id' => $this->product->getKey(),
            'name' => 'Searched product',
            'description_html' => 'Lorem ipsum',
            'search_values' => 'updated attribute',
        ]);
    }

    /**
     * @dataProvider authProvider
     */
    public function testDeleteAttributeSearchValues(string $user): void
    {
        $this->{$user}->givePermissionTo('attributes.remove');

        /** @var Attribute $attribute */
        $attribute = Attribute::factory()->create();
        $this->product->attributes()->attach($attribute->getKey());

        $this
            ->actingAs($this->{$user})
            ->json('DELETE', 'attributes/id:' . $attribute->getKey())
            ->assertNoContent();

        $this->assertDatabaseHas('products', [
            'id' => $this->product->getKey(),
            'name' => 'Searched product',
            'description_html' => 'Lorem ipsum',
            'search_values' => '',
        ]);
    }

    /**
     * @dataProvider authProvider
     */
    public function testUpdateAttributeOptionSearchValues(string $user): void
    {
        $this->{$user}->givePermissionTo('attributes.edit');

        /** @var Attribute $attribute */
        $attribute = Attribute::factory()->create([
            'name' => 'Attribute',
            'type' => AttributeType::NUMBER->value,
        ]);

        $option = AttributeOption::factory()->create([
            'index' => 1,
            'attribute_id' => $attribute->getKey(),
        ]);

        $this->product->attributes()->attach($attribute->getKey());
        $this->product->attributes->first()->pivot->options()->attach($option->getKey());

        $this
            ->actingAs($this->{$user})
            ->json('PATCH', 'attributes/id:' . $attribute->getKey() . '/options/id:' . $option->getKey(), [
                'name' => 'option 1',
                'value_number' => 10,
                'value_date' => '2023-09-08',
            ])
            ->assertOk();

        $this->assertDatabaseHas('products', [
            'id' => $this->product->getKey(),
            'name' => 'Searched product',
            'description_html' => 'Lorem ipsum',
            'search_values' => 'Attribute option 1 10 2023-09-08',
        ]);
    }

    /**
     * @dataProvider authProvider
     */
    public function testDeleteAttributeOptionSearchValues(string $user): void
    {
        $this->{$user}->givePermissionTo('attributes.edit');

        /** @var Attribute $attribute */
        $attribute = Attribute::factory()->create([
            'name' => 'Attribute',
            'type' => AttributeType::NUMBER->value,
        ]);

        $option = AttributeOption::factory()->create([
            'index' => 1,
            'attribute_id' => $attribute->getKey(),
        ]);

        $this->product->attributes()->attach($attribute->getKey());
        $this->product->attributes->first()->pivot->options()->attach($option->getKey());

        $this
            ->actingAs($this->{$user})
            ->json('DELETE', 'attributes/id:' . $attribute->getKey() . '/options/id:' . $option->getKey())
            ->assertNoContent();

        $this->assertDatabaseHas('products', [
            'id' => $this->product->getKey(),
            'name' => 'Searched product',
            'description_html' => 'Lorem ipsum',
            'search_values' => 'Attribute',
        ]);
    }
}
