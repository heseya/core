<?php

namespace Database\Seeders;

use App\Models\ProductSet;
use App\Models\Deposit;
use App\Models\Item;
use App\Models\Media;
use App\Models\Option;
use App\Models\Product;
use App\Models\Schema;
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

        $products->each(function ($product) use ($sets) {
            if (rand(0, 1)) {
                $this->schemas($product);
            }

            $this->media($product);
            $this->sets($product, $sets);
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
}
