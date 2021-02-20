<?php

namespace Database\Seeders;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Deposit;
use App\Models\Item;
use App\Models\Media;
use App\Models\Option;
use App\Models\Product;
use App\Models\Schema;
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
        $categories = array_merge(
            Category::factory()->count(3)->create(['public' => true])->all(),
            Category::factory()->count(2)->create(['public' => false])->all(),
        );

        $brands = array_merge(
            Brand::factory()->count(4)->create(['public' => true])->all(),
            Brand::factory()->count(2)->create(['public' => false])->all(),
        );

        Product::factory()->count(25)->create([
            'category_id' => $categories[rand(0, 2)]->getKey(),
            'brand_id' => $brands[rand(0, 3)]->getKey(),
            'public' => true,
        ])->each(fn ($p) => $this->media($p));

        Product::factory()->count(25)->create([
            'category_id' => $categories[rand(0, 2)]->getKey(),
            'brand_id' => $brands[rand(0, 3)]->getKey(),
            'public' => false,
        ])->each(fn ($p) => $this->media($p));

        Product::factory()->count(25)->create([
            'category_id' => $categories[rand(0, 2)]->getKey(),
            'brand_id' => $brands[rand(0, 3)]->getKey(),
            'public' => true,
        ])->each(fn ($p) => $this->complexProduct($p))->each(fn ($p) => $this->media($p));

        Product::factory()->count(25)->create([
            'category_id' => $categories[rand(0, 2)]->getKey(),
            'brand_id' => $brands[rand(0, 3)]->getKey(),
            'public' => false,
        ])->each(fn ($p) => $this->complexProduct($p))->each(fn ($p) => $this->media($p));

        Product::factory()->count(25)->create([
            'category_id' => $categories[rand(0, 2)]->getKey(),
            'brand_id' => $brands[rand(0, 3)]->getKey(),
            'public' => true,
        ])->each(fn ($p) => $this->simpleProduct($p))->each(fn ($p) => $this->media($p));

        Product::factory()->count(25)->create([
            'category_id' => $categories[rand(2, 4)]->getKey(),
            'brand_id' => $brands[rand(3, 5)]->getKey(),
            'public' => false,
        ])->each(fn ($p) => $this->simpleProduct($p))->each(fn ($p) => $this->media($p));

        Product::factory()->count(25)->create([
            'category_id' => $categories[rand(2, 4)]->getKey(),
            'brand_id' => $brands[rand(3, 5)]->getKey(),
            'public' => true,
        ])->each(fn ($p) => $this->complexProduct($p))->each(fn ($p) => $this->media($p));

        Product::factory()->count(25)->create([
            'category_id' => $categories[rand(2, 4)]->getKey(),
            'brand_id' => $brands[rand(3, 5)]->getKey(),
            'public' => false,
        ])->each(fn ($p) => $this->complexProduct($p))->each(fn ($p) => $this->media($p));
    }

    private function simpleProduct ($product) {
        $schema = Schema::factory()->make();

        $product->schemas()->save($schema);

        $item = Item::factory()->create();
        $item->deposits()->saveMany(Deposit::factory()->count(rand(0, 2))->make());
        $schema->options()->saveMany(Option::factory()->count(rand(0, 4))->make());
    }

    private function complexProduct($product)
    {
        $product->schemas();
    }

    private function media($product)
    {
        for ($i = 0; $i < rand(0, 5); $i++) {
            $media = Media::factory()->create();
            $product->media()->attach($media);
        }
    }
}