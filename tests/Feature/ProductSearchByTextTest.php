<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\ProductAttribute;
use Domain\ProductAttribute\Enums\AttributeType;
use Domain\ProductAttribute\Models\Attribute;
use Domain\ProductAttribute\Models\AttributeOption;
use Domain\ProductAttribute\Models\ProductAttributeOption;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Testing\RefreshDatabaseState;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

use function PHPUnit\Framework\assertContains;
use function PHPUnit\Framework\assertCount;
use function PHPUnit\Framework\assertEmpty;
use function PHPUnit\Framework\assertEquals;
use function PHPUnit\Framework\assertStringContainsString;
use function PHPUnit\Framework\assertStringStartsWith;

class ProductSearchByTextTest extends TestCase
{
    protected Attribute $attribute;
    protected Collection $products;
    protected Product $firstProduct;
    protected Product $problematicProduct;
    protected Product $problematicProduct2;
    protected string $problematicSku;

    private function prepareProducts(bool $commit = false)
    {
        Product::removeAllFromSearch();

        $this->attribute = Attribute::factory()->create([
            'name' => 'sku',
            'slug' => 'sku',
            'type' => AttributeType::SINGLE_OPTION,
            'include_in_text_search' => true,
        ]);

        $nameParts = [
            'bar',
            'foo',
            'ipsum',
            'lorem',
            'test',
        ];

        $permutatedNameParts = $this->computePermutations($nameParts);

        $productNames = [];
        foreach ($permutatedNameParts as $permutation) {
            $productNames[] = implode(' ', $permutation);
        }
        $productNames[] = 'HAMMER 3';
        $productNames[] = 'HAMMER 4';
        $productNames[] = 'HAMMER Blade 3';
        $productNames[] = 'HAMMER Blade 4';
        $productNames[] = 'HAMMER Blade 5G';
        $productNames[] = 'HAMMER Iron 3 LTE';
        $productNames[] = 'HAMMER Iron 4 Silver';
        $productNames[] = 'HAMMER Iron 4 Orange';
        $productNames[] = 'HAMMER Blade 3 + HAMMER Watch Plus';
        $productNames[] = 'HAMMER Blade 5G + HAMMER Watch';
        $productNames[] = 'HAMMER Energy X';
        $productNames[] = 'HAMMER Energy X + Ładowarka sieciowa';

        $this->products = new Collection();
        $i = 1;
        foreach ($productNames as $name) {
            $product = Product::factory()->create([
                'public' => true,
                'name' => $name,
                'description_html' => '',
                'description_short' => '',
                'search_values' => '',
            ]);

            $this->firstProduct ??= $product;

            $option = AttributeOption::factory()->create([
                'attribute_id' => $this->attribute->getKey(),
                'name' => ((string) ($i + 1000)) . 'SKU',
                'index' => $i,
            ]);

            $productAttribute = ProductAttribute::create([
                'product_id' => $product->getKey(),
                'attribute_id' => $this->attribute->getKey()
            ]);

            ProductAttributeOption::create([
                'attribute_option_id' => $option->getKey(),
                'product_attribute_id' => $productAttribute->getKey(),
            ]);

            $this->products->push($product);

            $i++;
        }

        $this->problematicProduct = Product::factory()->create([
            'public' => true,
            'name' => 'Laptop techbite Arc 11.6 128 GB HD',
            'description_html' => '',
            'description_short' => '',
            'search_values' => '',
        ]);
        $this->problematicSku = 'TECHBITE_ARC_128';
        $option = AttributeOption::factory()->create([
            'attribute_id' => $this->attribute->getKey(),
            'name' => $this->problematicSku,
            'index' => $i,
        ]);
        $productAttribute = ProductAttribute::create([
            'product_id' => $this->problematicProduct->getKey(),
            'attribute_id' => $this->attribute->getKey()
        ]);
        ProductAttributeOption::create([
            'attribute_option_id' => $option->getKey(),
            'product_attribute_id' => $productAttribute->getKey(),
        ]);

        $this->products->push($this->problematicProduct);

        $this->problematicProduct2 = Product::factory()->create([
            'public' => true,
            'name' => 'HAMMER Blade 4 + Ładowarka HAMMER',
            'description_html' => '',
            'description_short' => '',
            'search_values' => '',
        ]);
        $option = AttributeOption::factory()->create([
            'attribute_id' => $this->attribute->getKey(),
            'name' => ((string) ($i + 1000)) . 'SKU',
            'index' => $i,
        ]);
        $productAttribute = ProductAttribute::create([
            'product_id' => $this->problematicProduct2->getKey(),
            'attribute_id' => $this->attribute->getKey()
        ]);
        ProductAttributeOption::create([
            'attribute_option_id' => $option->getKey(),
            'product_attribute_id' => $productAttribute->getKey(),
        ]);

        $this->products->push($this->problematicProduct2);

        if ($commit) {
            DB::commit();
        }

        Product::makeAllSearchable();
    }

    protected function tearDown(): void
    {
        if (Config::get('search.use_full_text_query') || Config::get('search.use_full_text_relevancy')) {
            $this->cleanProducts();
        }
        parent::tearDown();
    }

    private function cleanProducts()
    {
        Product::removeAllFromSearch();

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        RefreshDatabaseState::$migrated = false;
        $this->refreshTestDatabase();

        Config::set('search.use_scout', false);
        Config::set('search.use_full_text_query', true);
        Config::set('search.use_full_text_relevancy', true);
    }

    private function computePermutations(array $array): array
    {
        $result = [];

        $recurse = function ($array, $start_i = 0) use (&$result, &$recurse) {
            if ($start_i === count($array) - 1) {
                array_push($result, $array);
            }

            for ($i = $start_i; $i < count($array); $i++) {
                //Swap array value at $i and $start_i
                $t = $array[$i];
                $array[$i] = $array[$start_i];
                $array[$start_i] = $t;

                //Recurse
                $recurse($array, $start_i + 1);

                //Restore old order
                $t = $array[$i];
                $array[$i] = $array[$start_i];
                $array[$start_i] = $t;
            }
        };

        $recurse($array);

        return $result;
    }

    public function testFulltextSearchByNameUsingLaravelFulltextSearch(): void
    {
        Config::set('search.use_scout', false);
        Config::set('search.use_full_text_query', true);
        Config::set('search.use_full_text_relevancy', true);

        $this->prepareProducts(true);

        $this->user->givePermissionTo('products.show');

        /** @var Product $product */
        $product = $this->products->where('name', 'bar foo ipsum lorem test')->first();

        $response = $this->actingAs($this->user)
            ->json('GET', '/products', [
                'search' => 'bar foo ipsum lorem test',
            ]);
        $response->assertOk();
        $data = $response->json('data');
        assertEquals($product->getKey(), $data[0]['id']);

        $response = $this->actingAs($this->user)
            ->json('GET', '/products', [
                'search' => 'bar foo ipsum',
            ]);
        $response->assertOk();
        $data = $response->json('data');
        assertStringStartsWith('bar foo ipsum', $data[0]['name']);

        $response = $this->actingAs($this->user)
            ->json('GET', '/products', [
                'search' => 'bar foo',
            ]);
        $response->assertOk();
        $data = $response->json('data');
        assertStringContainsString('bar foo', $data[0]['name']);

        $response = $this->actingAs($this->user)
            ->json('GET', '/products', [
                'search' => '"bar foo"',
            ]);
        $response->assertOk();
        $data = $response->json('data');
        assertStringContainsString('bar foo', $data[0]['name']);

        $response = $this->actingAs($this->user)
            ->json('GET', '/products', [
                'search' => '"bar foo" ipsum',
            ]);
        $response->assertOk();
        $data = $response->json('data');
        assertStringContainsString('bar foo', $data[0]['name']);
        assertStringContainsString('ipsum', $data[0]['name']);

        $response = $this->actingAs($this->user)
            ->json('GET', '/products', [
                'search' => 'Laptop techbite Arc 11.6 128 GB HD',
            ]);
        $response->assertOk();
        $data = $response->json('data');
        assertEquals($this->problematicProduct->getKey(), $data[0]['id']);

        $response = $this->actingAs($this->user)
            ->json('GET', '/products', [
                'search' => 'Laptop techbite Arc 11.6 128 HD',
            ]);
        $response->assertOk();
        $data = $response->json('data');
        assertEquals($this->problematicProduct->getKey(), $data[0]['id']);

        $response = $this->actingAs($this->user)
            ->json('GET', '/products', [
                'search' => 'Laptop   techbite Arc + 11.6 128 HD +',
            ]);
        $response->assertOk();
        $data = $response->json('data');
        assertEquals($this->problematicProduct->getKey(), $data[0]['id']);

        $response = $this->actingAs($this->user)
            ->json('GET', '/products', [
                'search' => 'Hammer 4',
            ]);
        $response->assertOk();
        $data = $response->json('data');
        assertEquals('HAMMER 4', $data[0]['name']);

        $response = $this->actingAs($this->user)
            ->json('GET', '/products', [
                'search' => 'Gammer 4',
            ]);
        $response->assertOk();
        $data = $response->json('data');
        assertEmpty($data); // no fuzzy search :(

        $response = $this->actingAs($this->user)
            ->json('GET', '/products', [
                'search' => 'Hammer Blade 4',
            ]);
        $response->assertOk();
        $data = $response->json('data');
        assertContains($data[0]['name'], [
            'HAMMER Blade 4',
            'HAMMER Blade 4 + Ładowarka HAMMER',
        ]);

        $response = $this->actingAs($this->user)
            ->json('GET', '/products', [
                'search' => 'Blade',
            ]);
        $response->assertOk();
        $data = $response->json('data');
        assertStringContainsString('Blade', $data[0]['name']);

        $response = $this->actingAs($this->user)
            ->json('GET', '/products', [
                'search' => 'Ładowarka',
            ]);
        $response->assertOk();
        $data = $response->json('data');
        assertEquals('HAMMER Blade 4 + Ładowarka HAMMER', $data[0]['name']);
    }

    public function testFulltextSearchByNameUsingLikeAllWordsQuerySearch(): void
    {
        Config::set('search.use_scout', false);
        Config::set('search.use_full_text_query', false);
        Config::set('search.use_full_text_relevancy', true);

        $this->prepareProducts(true);

        $this->user->givePermissionTo('products.show');

        /** @var Product $product */
        $product = $this->products->where('name', 'bar foo ipsum lorem test')->first();

        $response = $this->actingAs($this->user)
            ->json('GET', '/products', [
                'search' => 'bar foo ipsum lorem test',
            ]);
        $response->assertOk();
        $data = $response->json('data');
        assertEquals($product->getKey(), $data[0]['id']);

        $response = $this->actingAs($this->user)
            ->json('GET', '/products', [
                'search' => 'bar foo ipsum',
            ]);
        $response->assertOk();
        $data = $response->json('data');
        assertStringStartsWith('bar foo ipsum', $data[0]['name']);

        $response = $this->actingAs($this->user)
            ->json('GET', '/products', [
                'search' => 'bar foo',
            ]);
        $response->assertOk();
        $data = $response->json('data');
        assertStringContainsString('bar foo', $data[0]['name']);

        $response = $this->actingAs($this->user)
            ->json('GET', '/products', [
                'search' => '"bar foo"',
            ]);
        $response->assertOk();
        $data = $response->json('data');
        assertStringContainsString('bar foo', $data[0]['name']);

        $response = $this->actingAs($this->user)
            ->json('GET', '/products', [
                'search' => '"bar foo" ipsum',
            ]);
        $response->assertOk();
        $data = $response->json('data');
        assertStringContainsString('bar foo', $data[0]['name']);
        assertStringContainsString('ipsum', $data[0]['name']);

        $response = $this->actingAs($this->user)
            ->json('GET', '/products', [
                'search' => 'Laptop techbite Arc 11.6 128 GB HD',
            ]);
        $response->assertOk();
        $data = $response->json('data');
        assertEquals($this->problematicProduct->getKey(), $data[0]['id']);

        $response = $this->actingAs($this->user)
            ->json('GET', '/products', [
                'search' => 'Laptop techbite Arc 11.6 128 HD',
            ]);
        $response->assertOk();
        $data = $response->json('data');
        assertEquals($this->problematicProduct->getKey(), $data[0]['id']);

        $response = $this->actingAs($this->user)
            ->json('GET', '/products', [
                'search' => 'Laptop   techbite Arc + 11.6 128 HD +',
            ]);
        $response->assertOk();
        $data = $response->json('data');
        assertEquals($this->problematicProduct->getKey(), $data[0]['id']);

        $response = $this->actingAs($this->user)
            ->json('GET', '/products', [
                'search' => 'Hammer 4',
            ]);
        $response->assertOk();
        $data = $response->json('data');
        assertEquals('HAMMER 4', $data[0]['name']);

        $response = $this->actingAs($this->user)
            ->json('GET', '/products', [
                'search' => 'Gammer 4',
            ]);
        $response->assertOk();
        $data = $response->json('data');
        assertEmpty($data); // no fuzzy search :(

        $response = $this->actingAs($this->user)
            ->json('GET', '/products', [
                'search' => 'Hammer Blade 4',
            ]);
        $response->assertOk();
        $data = $response->json('data');
        assertContains($data[0]['name'], [
            'HAMMER Blade 4',
            'HAMMER Blade 4 + Ładowarka HAMMER',
        ]);

        $response = $this->actingAs($this->user)
            ->json('GET', '/products', [
                'search' => 'Blade',
            ]);
        $response->assertOk();
        $data = $response->json('data');
        assertStringContainsString('Blade', $data[0]['name']);

        $response = $this->actingAs($this->user)
            ->json('GET', '/products', [
                'search' => 'hammer 5G',
            ]);
        $response->assertOk();
        $data = $response->json('data');
        assertContains($data[0]['name'], [
            'HAMMER Blade 5G',
            'HAMMER Blade 5G + HAMMER Watch',
        ]);

        $response = $this->actingAs($this->user)
            ->json('GET', '/products', [
                'search' => '5G',
            ]);
        $response->assertOk();
        $data = $response->json('data');
        assertContains($data[0]['name'], [
            'HAMMER Blade 5G',
            'HAMMER Blade 5G + HAMMER Watch',
        ]);

        $response = $this->actingAs($this->user)
            ->json('GET', '/products', [
                'search' => 'Ładowarka',
            ]);
        $response->assertOk();
        $data = $response->json('data');
        assertEquals('HAMMER Blade 4 + Ładowarka HAMMER', $data[0]['name']);
    }

    public function testFulltextSearchByNameUsingScoutEngineTnt(): void
    {
        Config::set('search.use_scout', true);
        Config::set('search.use_full_text_query', false);
        Config::set('search.use_full_text_relevancy', false);
        Config::set('scout.driver', 'tntsearch');

        $this->prepareProducts();

        $this->user->givePermissionTo('products.show');

        /** @var Product $product */
        $product = $this->products->where('name', 'bar foo ipsum lorem test')->first();

        $response = $this->actingAs($this->user)
            ->json('GET', '/products', [
                'search' => 'bar foo ipsum lorem test',
            ]);
        $response->assertOk();
        $data = $response->json('data');
        assertEquals($product->getKey(), $data[0]['id']);

        $response = $this->actingAs($this->user)
            ->json('GET', '/products', [
                'search' => 'bar foo ipsum',
            ]);

        $response->assertOk();
        $data = $response->json('data');
        assertEquals($product->getKey(), $data[0]['id']);

        $response = $this->actingAs($this->user)
            ->json('GET', '/products', [
                'search' => 'bar foo',
            ]);
        $response->assertOk();
        $data = $response->json('data');
        assertEquals($product->getKey(), $data[0]['id']);

        $response = $this->actingAs($this->user)
            ->json('GET', '/products', [
                'search' => 'Laptop techbite Arc 11.6 128 GB HD',
            ]);
        $response->assertOk();
        $data = $response->json('data');
        assertEquals($this->problematicProduct->getKey(), $data[0]['id']);

        $response = $this->actingAs($this->user)
            ->json('GET', '/products', [
                'search' => 'Laptop techbite Arc 11.6 128 HD',
            ]);
        $response->assertOk();
        $data = $response->json('data');
        assertEquals($this->problematicProduct->getKey(), $data[0]['id']);

        $response = $this->actingAs($this->user)
            ->json('GET', '/products', [
                'search' => 'Hammer 4',
            ]);
        $response->assertOk();
        $data = $response->json('data');
        assertContains($data[0]['name'], [
            'HAMMER 4',
            'HAMMER Blade 4 + Ładowarka HAMMER',
        ]);

        $response = $this->actingAs($this->user)
            ->json('GET', '/products', [
                'search' => 'Gammer 4',
            ]);
        $response->assertOk();
        $data = $response->json('data');
        assertContains($data[0]['name'], [
            'HAMMER 4',
            'HAMMER Blade 4 + Ładowarka HAMMER',
        ]); // fuzzy search :)

        $response = $this->actingAs($this->user)
            ->json('GET', '/products', [
                'search' => 'Hammer Blade 4',
            ]);
        $response->assertOk();
        $data = $response->json('data');
        assertContains($data[0]['name'], [
            'HAMMER Blade 4',
            'HAMMER Blade 4 + Ładowarka HAMMER',
        ]);

        $response = $this->actingAs($this->user)
            ->json('GET', '/products', [
                'search' => 'Blade',
            ]);
        $response->assertOk();
        $data = $response->json('data');
        assertStringContainsString('Blade', $data[0]['name']);

        $response = $this->actingAs($this->user)
            ->json('GET', '/products', [
                'search' => 'hammer 5G',
            ]);
        $response->assertOk();
        $data = $response->json('data');
        assertContains($data[0]['name'], [
            'HAMMER Blade 5G',
            'HAMMER Blade 5G + HAMMER Watch',
        ]);

        $response = $this->actingAs($this->user)
            ->json('GET', '/products', [
                'search' => '5G',
            ]);
        $response->assertOk();
        $data = $response->json('data');
        assertContains($data[0]['name'], [
            'HAMMER Blade 5G',
            'HAMMER Blade 5G + HAMMER Watch',
        ]);

        $response = $this->actingAs($this->user)->json('GET', '/products?search=%C5%81adowarka');
        $response->assertOk();
        $data = $response->json('data');
        assertContains($data[0]['name'], [
            'HAMMER Energy X + Ładowarka sieciowa',
            'HAMMER Blade 4 + Ładowarka HAMMER'
        ]);
    }

    public function testFulltextSearchBySkuUsingLaravelFulltextSearch(): void
    {
        Config::set('search.use_scout', false);
        Config::set('search.use_full_text_query', true);
        Config::set('search.use_full_text_relevancy', true);

        $this->prepareProducts(true);

        $this->user->givePermissionTo('products.show');

        $sku = '1001SKU';

        $response = $this->actingAs($this->user)
            ->json('GET', '/products', [
                'search' => $sku,
            ]);
        $response->assertOk();
        $data = $response->json('data');
        assertEquals($this->firstProduct->getKey(), $data[0]['id']);
        assertCount(1, $data);

        $response = $this->actingAs($this->user)
            ->json('GET', '/products', [
                'search' => $this->problematicSku,
            ]);
        $response->assertOk();
        $data = $response->json('data');
        assertEquals($this->problematicProduct->getKey(), $data[0]['id']);
        assertCount(1, $data);
    }

    public function testFulltextSearchBySkuUsingScoutEngineTnt(): void
    {
        Config::set('search.use_scout', true);
        Config::set('search.use_full_text_query', false);
        Config::set('search.use_full_text_relevancy', false);
        Config::set('scout.driver', 'tntsearch');

        $this->prepareProducts();

        $this->user->givePermissionTo('products.show');

        $sku = '1001SKU';

        $response = $this->actingAs($this->user)
            ->json('GET', '/products', [
                'search' => $sku,
            ]);
        $response->assertOk();
        $data = $response->json('data');
        assertEquals($this->firstProduct->getKey(), $data[0]['id']);

        $response = $this->actingAs($this->user)
            ->json('GET', '/products', [
                'search' => $this->problematicSku,
            ]);
        $response->assertOk();
        $data = $response->json('data');
        assertEquals($this->problematicProduct->getKey(), $data[0]['id']);
        assertCount(1, $data);
    }
}
