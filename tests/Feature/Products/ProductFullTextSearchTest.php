<?php

namespace Tests\Feature\Products;

use App\Models\Attribute;
use App\Models\AttributeOption;
use App\Models\Product;
use App\Models\ProductSet;
use App\Services\ProductService;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Tests\TestCase;

class ProductFullTextSearchTest extends TestCase
{
    use DatabaseMigrations;

    public Product $product;

    public function setUp(): void
    {
        parent::setUp();

        $this->product = Product::factory()->create([
            'public' => true,
            'name' => 'Searched product',
            'description_html' => 'Description',
            'description_short' => 'short',
        ]);

        $parentSet = ProductSet::factory()->create([
            'public' => true,
            'name' => 'Parent',
        ]);
        $set = ProductSet::factory()->create([
            'public' => true,
            'name' => 'Set',
            'parent_id' => $parentSet->getKey(),
        ]);
        $this->product->sets()->sync($set->getKey());
        $relatedSet = ProductSet::factory()->create([
            'public' => true,
            'name' => 'Related',
        ]);
        $this->product->relatedSets()->sync($relatedSet->getKey());

        $attribute = Attribute::factory()->create([
            'name' => 'Attribute',
        ]);
        $option = AttributeOption::factory()->create([
            'index' => 1,
            'attribute_id' => $attribute->getKey(),
            'name' => 'Option 1',
            'value_number' => 10,
            'value_date' => '2023-09-08',
        ]);
        $this->product->attributes()->attach($attribute->getKey());
        $this->product->attributes->first()->pivot->options()->attach($option->getKey());

        app(ProductService::class)->updateProductsSearchValues([$this->product->getKey()]);
    }

    public static function fullSearchProvider(): array
    {
        return [
            'as user by name' => ['user', 'product'],
            'as user by parent set' => ['user', 'Parent'],
            'as user by set' => ['user', 'Set'],
            'as user by related set' => ['user', 'Related'],
            'as user by attribute' => ['user', 'Attribute'],
            'as user by attribute option' => ['user', 'Option 1'],
            'as app by name' => ['application', 'product'],
            'as app by parent set' => ['application', 'Parent'],
            'as app by set' => ['application', 'Set'],
            'as app by related set' => ['application', 'Related'],
            'as app by attribute' => ['application', 'Attribute'],
            'as app by attribute option' => ['application', 'Option 1'],
        ];
    }

    /**
     * @dataProvider fullSearchProvider
     */
    public function testSearchByName(string $user, string $search): void
    {
        $this->{$user}->givePermissionTo('products.show');

        Product::factory()->count(5)->create([
            'public' => true,
        ]);

        $this
            ->actingAs($this->{$user})
            ->json('GET', '/products', ['search' => $search])
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonFragment(['id' => $this->product->getKey()]);
    }
}
