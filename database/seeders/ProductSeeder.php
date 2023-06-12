<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\SchemaType;
use App\Models\Deposit;
use App\Models\Item;
use App\Models\Media;
use App\Models\Option;
use App\Models\Price;
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
        Product::withoutSyncingToSearch(function (): void {
            /** @var ProductServiceContract $productService */
            $productService = App::make(ProductServiceContract::class);

            $products = $this->createProducts(100);

            $sets = ProductSet::all();
            $brands = $this->createBrands();
            $categories = $this->createCategories();

            $this->setupProduct($products, $sets);
            $this->attachRandomBrandOrCategory($products, $brands, $categories);
            $this->persistsProducts($products, $productService);

            $this->setAvailability();
        });
    }

    private function createProducts(int $count)
    {
        $product = Product::factory()->count($count)
            ->state(fn ($sequence) => [
                'shipping_digital' => rand(0, 1),
            ])
            ->create();

        //        'price' => round(rand(500, 6000), -2)
        //        $price = Price::factory([
        //            'type' => 'price',
        //        ])->create();

        return $product;
    }

    /**
     * @param Collection<Product> $products
     * @param Collection<ProductSet> $sets
     */
    private function setupProduct(
        Collection $products,
        Collection $sets,
    ): void {
        $products->each(function (Product $product) use ($sets) {
            if (rand(0, 1)) {
                $this->attachNewSchema($product);
            }

            $this->attachNewMedia($product);
            $this->attachRandomSets($product, $sets);
            $this->attachNewSeo($product);
        });
    }

    /**
     * @param Collection<Product> $products
     */
    private function persistsProducts(Collection $products, ProductServiceContract $productService): void
    {
        $products->each(function (Product $product) use ($productService) {
            $product->refresh();
            $product->save();
            $productService->updateMinMaxPrices($product);
        });
    }

    /**
     * @param Collection<Product> $products
     * @param Collection<ProductSet> $brands
     * @param Collection<ProductSet> $categories
     */
    private function attachRandomBrandOrCategory(Collection $products, Collection $brands, Collection $categories): void
    {
        $products->split(4);

        $products->pop()
            ->each(fn (Product $product) => $this->attachRandomSet($product, $brands));

        $products->pop()
            ->each(fn (Product $product) => $this->attachRandomSet($product, $categories));

        $products->pop()
            ->each(function (Product $product) use ($brands, $categories) {
                $this->attachRandomSet($product, $brands);
                $this->attachRandomSet($product, $categories);
            });
    }

    /** @return Collection<ProductSet> */
    private function createBrands(): Collection
    {
        $brandsRoot = ProductSet::factory([
            'name' => 'Brands',
            'slug' => 'brands',
        ])->make();
        $this->attachNewSeo($brandsRoot);

        $brands = ProductSet::factory([
            'parent_id' => $brandsRoot->getKey(),
        ])->count(4)->create();
        $brands->each(fn ($set) => $this->attachNewSeo($set));

        return $brands;
    }

    /** @return Collection<ProductSet> */
    private function createCategories(): Collection
    {
        $categoryRoot = ProductSet::factory([
            'name' => 'Categories',
            'slug' => 'categories',
        ])->create();
        $this->attachNewSeo($categoryRoot);

        $categories = ProductSet::factory([
            'parent_id' => $categoryRoot->getKey(),
        ])->count(4)->create();
        $categories->each(fn ($set) => $this->attachNewSeo($set));

        return $categories;
    }

    private function attachNewSeo(Product|ProductSet $product): void
    {
        $seo = SeoMetadata::factory()->create();
        $product->seo()->save($seo);
    }

    private function attachNewSchema(Product $product): void
    {
        /** @var Schema $schema */
        $schema = Schema::factory()->create([
            'type' => rand(0, 6), // all types except multiply_schemas
        ]);
        $product->schemas()->attach($schema->getKey());

        if ($schema->type->is(SchemaType::SELECT)) {
            /** @var Item $item */
            $item = Item::factory()->create();
            $item->deposits()->saveMany(Deposit::factory()->count(rand(0, 2))->make());
            $schema->options()->saveMany(Option::factory()->count(rand(0, 4))->make());
            //            $schema->options->each(
            //                fn (Option $option) => $option->price()->save(Price::factory()->make()),
            //            );
        }
    }

    private function attachNewMedia(Product $product): void
    {
        for ($i = 0; $i < rand(0, 5); ++$i) {
            $media = Media::factory()->create();
            $product->media()->attach($media);
        }
    }

    private function attachRandomSets(Product $product, Collection $sets): void
    {
        for ($i = 0; $i < rand(0, 3); ++$i) {
            $this->attachRandomSet($product, $sets);
        }
    }

    private function attachRandomSet(Product $product, Collection $set): void
    {
        $product->sets()->syncWithoutDetaching($set->random());
    }

    private function setAvailability(): void
    {
        /** @var AvailabilityServiceContract $availabilityService */
        $availabilityService = App::make(AvailabilityServiceContract::class);
        $products = Product::all();

        $products->each(fn (Product $product) => $availabilityService->calculateProductAvailability($product));
    }
}
