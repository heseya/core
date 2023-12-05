<?php

namespace Tests\Feature;

use App\Models\AuthProvider;
use App\Models\Permission;
use App\Models\Product;
use App\Models\ProductAttribute;
use App\Models\Role;
use App\Models\Status;
use App\Models\User;
use Domain\Language\Language;
use Domain\ProductAttribute\Enums\AttributeType;
use Domain\ProductAttribute\Models\Attribute;
use Domain\ProductAttribute\Models\AttributeOption;
use Domain\ProductAttribute\Models\ProductAttributeOption;
use Domain\Seo\Models\SeoMetadata;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Testing\RefreshDatabaseState;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

use function PHPUnit\Framework\assertEquals;

class ProductSearchByTextTest extends TestCase
{
    protected Attribute $attribute;
    protected Collection $products;
    protected Product $firstProduct;

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

        if ($commit) {
            DB::commit();
        }

        Product::makeAllSearchable();
    }

    private function cleanProducts()
    {
        Product::removeAllFromSearch();

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        RefreshDatabaseState::$migrated = false;
        $this->refreshTestDatabase();

        Config::set('search.use_scout', false);
        Config::set('search.use_full_text_query', true);
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
        assertEquals($product->getKey(), $data[0]['id']);

        $response = $this->actingAs($this->user)
            ->json('GET', '/products', [
                'search' => 'bar foo',
            ]);
        $response->assertOk();
        $data = $response->json('data');
        assertEquals($product->getKey(), $data[0]['id']);

        $this->cleanProducts();
    }

    public function testFulltextSearchByNameUsingScoutEngineTnt(): void
    {
        Config::set('search.use_scout', true);
        Config::set('search.use_full_text_query', false);
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
    }

    public function testFulltextSearchBySkuUsingLaravelFulltextSearch(): void
    {
        Config::set('search.use_scout', false);
        Config::set('search.use_full_text_query', true);

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

        $this->cleanProducts();
    }

    public function testFulltextSearchBySkuUsingScoutEngineTnt(): void
    {
        Config::set('search.use_scout', true);
        Config::set('search.use_full_text_query', false);
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
    }
}
