<?php

namespace Database\Seeders;

use App\Models\ProductSet;
use App\Models\Deposit;
use App\Models\Item;
use App\Models\Media;
use App\Models\Option;
use App\Models\Product;
use App\Models\Schema;
use App\Models\SeoMetadata;
use App\Services\Contracts\ProductServiceContract;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\App;

class ProductSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        /** @var ProductServiceContract $productService */
        $productService = App::make(ProductServiceContract::class);

        $products = Product::factory()->count(100)->create();

        $sets = ProductSet::all();

        $brands = ProductSet::factory([
            'name' => 'Brands',
            'slug' => 'brands',
        ])->create();
        $this->seo($brands);
        $brands = ProductSet::factory([
            'parent_id' => $brands->getKey(),
        ])->count(4)->create();

        $brands->each(fn ($set) => $this->seo($set));

        $categories = ProductSet::factory([
            'name' => 'Categories',
            'slug' => 'categories',
        ])->create();
        $this->seo($categories);
        $categories = ProductSet::factory([
            'parent_id' => $categories->getKey(),
        ])->count(4)->create();

        $categories->each(fn ($set) => $this->seo($set));

        $products->each(function ($product, $index) use ($sets, $brands, $categories, $productService) {
            if (rand(0, 1)) {
                $this->schemas($product);
            }

            $this->media($product);
            $this->sets($product, $sets);
            $this->seo($product);

            if ($index >= 75) {
                $this->brands($product, $brands);
            } elseif ($index >= 50) {
                $this->categories($product, $categories);
            } elseif ($index >= 25) {
                $this->brands($product, $brands);
                $this->categories($product, $categories);
            }

            $productService->updateMinMaxPrices($product);
        });
    }

    private function schemas(Product $product): void
    {
        $schema = Schema::factory()->make();

        $product->schemas()->save($schema);

        $item = Item::factory()->create();
        $item->deposits()->saveMany(Deposit::factory()->count(rand(0, 2))->make());
        $schema->options()->saveMany(Option::factory()->count(rand(0, 4))->make());
    }

    private function media(Product $product): void
    {
        for ($i = 0; $i < rand(0, 5); $i++) {
            $media = Media::factory()->create();
            $product->media()->attach($media);
        }
    }

    private function sets(Product $product, Collection $sets): void
    {
        for ($i = 0; $i < rand(0, 3); $i++) {
            $product->sets()->syncWithoutDetaching($sets->random());
        }
    }

    private function categories(Product $product, Collection $categories): void
    {
        $product->sets()->syncWithoutDetaching($categories->random());
    }

    private function brands(Product $product, Collection $brands): void
    {
        $product->sets()->syncWithoutDetaching($brands->random());
    }

    private function seo(Product|ProductSet $product): void
    {
        $seo = SeoMetadata::factory()->create();
        $product->seo()->save($seo);
    }
}
