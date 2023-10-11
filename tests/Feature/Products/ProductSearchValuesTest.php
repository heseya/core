<?php

namespace Tests\Feature\Products;

use App\Models\Product;
use Domain\ProductAttribute\Enums\AttributeType;
use Domain\ProductAttribute\Models\Attribute;
use Domain\ProductAttribute\Models\AttributeOption;
use Domain\ProductSet\ProductSet;
use Domain\Tag\Models\Tag;
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

    public function testTagSearchValues(): void
    {
        $tag = Tag::factory()->create([
            'name' => 'Tag updated',
        ]);
        $this->product->tags()->sync($tag->getKey());

        $this->artisan('products:update-index', ['id' => $this->product->getKey()]);

        $this->assertDatabaseHas('products', [
            'id' => $this->product->getKey(),
            "name->{$this->lang}" => 'Searched product',
            "description_html->{$this->lang}" => 'Lorem ipsum',
            'search_values' => 'Tag updated',
        ]);
    }

    /**
     * @dataProvider authProvider
     */
    public function testAfterDeleteTagSearchValues(string $user): void
    {
        $this->{$user}->givePermissionTo('tags.remove');

        /** @var Tag $tag */
        $tag = Tag::factory()->create();
        $this->product->tags()->sync($tag->getKey());

        $this->artisan('products:update-index', ['id' => $this->product->getKey()]);

        $this->assertDatabaseHas('products', [
            'id' => $this->product->getKey(),
            "name->{$this->lang}" => 'Searched product',
            "description_html->{$this->lang}" => 'Lorem ipsum',
            'search_values' => $tag->name,
        ]);

        $this
            ->actingAs($this->{$user})
            ->json('DELETE', 'tags/id:' . $tag->getKey())
            ->assertNoContent();

        $this->artisan('products:update-index', ['id' => $this->product->getKey()]);

        $this->assertDatabaseHas('products', [
            'id' => $this->product->getKey(),
            "name->{$this->lang}" => 'Searched product',
            "description_html->{$this->lang}" => 'Lorem ipsum',
            'search_values' => '',
        ]);
    }

    public function testProductSetSearchValues(): void
    {
        $set = ProductSet::factory()->create([
            'public' => true,
            'name' => 'set name',
        ]);
        $this->product->sets()->sync($set->getKey());

        $this->artisan('products:update-index', ['id' => $this->product->getKey()]);

        $this->assertDatabaseHas('products', [
            'id' => $this->product->getKey(),
            "name->{$this->lang}" => 'Searched product',
            "description_html->{$this->lang}" => 'Lorem ipsum',
            'search_values' => 'set name',
        ]);
    }

    /**
     * @dataProvider authProvider
     */
    public function testAfterDeleteProductSetSearchValues(string $user): void
    {
        $this->{$user}->givePermissionTo('product_sets.remove');

        /** @var ProductSet $set */
        $set = ProductSet::factory()->create([
            'public' => true,
        ]);
        $this->product->sets()->sync($set->getKey());

        $this->artisan('products:update-index', ['id' => $this->product->getKey()]);

        $this->assertDatabaseHas('products', [
            'id' => $this->product->getKey(),
            "name->{$this->lang}" => 'Searched product',
            "description_html->{$this->lang}" => 'Lorem ipsum',
            'search_values' => $set->name,
        ]);

        $this
            ->actingAs($this->{$user})
            ->json('DELETE', 'product-sets/id:' . $set->getKey())
            ->assertNoContent();

        $this->artisan('products:update-index', ['id' => $this->product->getKey()]);

        $this->assertDatabaseHas('products', [
            'id' => $this->product->getKey(),
            "name->{$this->lang}" => 'Searched product',
            "description_html->{$this->lang}" => 'Lorem ipsum',
            'search_values' => '',
        ]);
    }

    public function testUpdateAttributeSearchValues(): void
    {
        /** @var Attribute $attribute */
        $attribute = Attribute::factory()->create([
            'name' => 'updated attribute',
        ]);
        $this->product->attributes()->attach($attribute->getKey());

        $this->artisan('products:update-index', ['id' => $this->product->getKey()]);

        $this->assertDatabaseHas('products', [
            'id' => $this->product->getKey(),
            "name->{$this->lang}" => 'Searched product',
            "description_html->{$this->lang}" => 'Lorem ipsum',
            'search_values' => 'updated attribute',
        ]);
    }

    /**
     * @dataProvider authProvider
     */
    public function testAfterDeleteAttributeSearchValues(string $user): void
    {
        $this->{$user}->givePermissionTo('attributes.remove');

        /** @var Attribute $attribute */
        $attribute = Attribute::factory()->create();
        $this->product->attributes()->attach($attribute->getKey());

        $this->artisan('products:update-index', ['id' => $this->product->getKey()]);

        $this->assertDatabaseHas('products', [
            'id' => $this->product->getKey(),
            "name->{$this->lang}" => 'Searched product',
            "description_html->{$this->lang}" => 'Lorem ipsum',
            'search_values' => $attribute->name,
        ]);

        $this
            ->actingAs($this->{$user})
            ->json('DELETE', 'attributes/id:' . $attribute->getKey())
            ->assertNoContent();

        $this->artisan('products:update-index', ['id' => $this->product->getKey()]);

        $this->assertDatabaseHas('products', [
            'id' => $this->product->getKey(),
            "name->{$this->lang}" => 'Searched product',
            "description_html->{$this->lang}" => 'Lorem ipsum',
            'search_values' => '',
        ]);
    }

    public function testUpdateAttributeOptionSearchValues(): void
    {
        /** @var Attribute $attribute */
        $attribute = Attribute::factory()->create([
            'name' => 'Attribute',
            'type' => AttributeType::NUMBER->value,
        ]);

        $option = AttributeOption::factory()->create([
            'index' => 1,
            'attribute_id' => $attribute->getKey(),
            'name' => 'option 1',
            'value_number' => 10,
            'value_date' => '2023-09-08',
        ]);

        $this->product->attributes()->attach($attribute->getKey());
        $this->product->attributes->first()->pivot->options()->attach($option->getKey());

        $this->artisan('products:update-index', ['id' => $this->product->getKey()]);

        $this->assertDatabaseHas('products', [
            'id' => $this->product->getKey(),
            "name->{$this->lang}" => 'Searched product',
            "description_html->{$this->lang}" => 'Lorem ipsum',
            'search_values' => 'Attribute option 1 10 2023-09-08',
        ]);
    }

    /**
     * @dataProvider authProvider
     */
    public function testAfterDeleteAttributeOptionSearchValues(string $user): void
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

        $this->artisan('products:update-index', ['id' => $this->product->getKey()]);

        $this
            ->actingAs($this->{$user})
            ->json('DELETE', 'attributes/id:' . $attribute->getKey() . '/options/id:' . $option->getKey())
            ->assertNoContent();

        $this->artisan('products:update-index', ['id' => $this->product->getKey()]);

        $this->assertDatabaseHas('products', [
            'id' => $this->product->getKey(),
            "name->{$this->lang}" => 'Searched product',
            "description_html->{$this->lang}" => 'Lorem ipsum',
            'search_values' => 'Attribute',
        ]);
    }
}
