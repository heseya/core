<?php

namespace Database\Seeders;

use App\Enums\SchemaType;
use App\Models\Deposit;
use App\Models\Item;
use App\Models\Media;
use App\Models\Option;
use App\Models\Product;
use App\Models\ProductSet;
use App\Models\Schema;
use App\Models\SeoMetadata;
use App\Services\Contracts\AvailabilityServiceContract;
use App\Services\Contracts\ProductServiceContract;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\App;

class ProductSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        /** @var ProductServiceContract $productService */
        $productService = App::make(ProductServiceContract::class);

        $products = Product::factory()->count(100)
            ->state(fn ($sequence) => [
                'shipping_digital' => mt_rand(0, 1),
            ])
            ->create();

        $sets = ProductSet::all();

        $brands = ProductSet::factory([
            'name' => 'Brands',
            'slug' => 'brands',
        ])->make();
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

        $products->each(function ($product, $index) use ($sets, $brands, $categories, $productService): void {
            if (mt_rand(0, 1)) {
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

            $product->refresh();
            $product->save();
            $productService->updateMinMaxPrices($product);
        });

        $this->setAvailability();
    }

    private function seo(Product|ProductSet $product): void
    {
        $seo = SeoMetadata::factory()->create();
        $product->seo()->save($seo);
    }

    private function schemas(Product $product): void
    {
        /** @var Schema $schema */
        $schema = Schema::factory()->create([
            'type' => mt_rand(0, 6), // all types except multiply_schemas
        ]);
        $product->schemas()->attach($schema->getKey());

        if ($schema->type->is(SchemaType::SELECT)) {
            /** @var Item $item */
            $item = Item::factory()->create();
            $item->deposits()->saveMany(Deposit::factory()->count(mt_rand(0, 2))->make());
            $schema->options()->saveMany(Option::factory()->count(mt_rand(0, 4))->make());
        }
    }

    private function media(Product $product): void
    {
        for ($i = 0; $i < mt_rand(0, 5); ++$i) {
            $media = Media::factory()->create();
            $product->media()->attach($media);
        }
    }

    private function sets(Product $product, Collection $sets): void
    {
        for ($i = 0; $i < mt_rand(0, 3); ++$i) {
            $product->sets()->syncWithoutDetaching($sets->random());
        }
    }

    private function brands(Product $product, Collection $brands): void
    {
        $product->sets()->syncWithoutDetaching($brands->random());
    }

    private function categories(Product $product, Collection $categories): void
    {
        $product->sets()->syncWithoutDetaching($categories->random());
    }

    private function setAvailability(): void
    {
        /** @var AvailabilityServiceContract $availabilityService */
        $availabilityService = App::make(AvailabilityServiceContract::class);
        $products = Product::all();

        $products->each(fn (Product $product) => $availabilityService->calculateProductAvailability($product));
    }
}
