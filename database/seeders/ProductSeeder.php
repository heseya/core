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
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $products = Product::factory()->count(100)->create();

        $sets = ProductSet::all();

        $brands = ProductSet::factory([
            'name' => 'Brands',
            'slug' => 'brands',
        ])->create();
        $brands = ProductSet::factory([
            'parent_id' => $brands->getKey(),
        ])->count(4)->create();

        $categories = ProductSet::factory([
            'name' => 'Categories',
            'slug' => 'categories',
        ])->create();
        $categories = ProductSet::factory([
            'parent_id' => $categories->getKey(),
        ])->count(4)->create();

        $products->each(function ($product, $index) use ($sets, $brands, $categories) {
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
        $product->update([
            'category_id' => $categories->random()->getKey(),
        ]);
    }

    private function brands(Product $product, Collection $brands): void
    {
        $product->update([
            'brand_id' => $brands->random()->getKey(),
        ]);
    }

    private function seo(Product $product): void
    {
        $seo = SeoMetadata::factory()->create();
        $product->seo()->save($seo);
    }
}
