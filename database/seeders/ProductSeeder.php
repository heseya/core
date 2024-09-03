<?php

namespace Database\Seeders;

use App\Models\Deposit;
use App\Models\Item;
use App\Models\Media;
use App\Models\Option;
use App\Models\Price;
use App\Models\Product;
use App\Services\Contracts\AvailabilityServiceContract;
use App\Services\ProductService;
use Brick\Math\Exception\NumberFormatException;
use Brick\Math\Exception\RoundingNecessaryException;
use Brick\Money\Exception\UnknownCurrencyException;
use Brick\Money\Money;
use Domain\Currency\Currency;
use Domain\Language\Language;
use Domain\Price\Dtos\PriceDto;
use Domain\PriceMap\PriceMapService;
use Domain\ProductSchema\Models\Schema;
use Domain\ProductSet\ProductSet;
use Domain\Seo\Models\SeoMetadata;
use Heseya\Dto\DtoException;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Seeder;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\App;
use Tests\Utils\FakeDto;

class ProductSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @throws DtoException
     * @throws NumberFormatException
     * @throws RoundingNecessaryException
     * @throws UnknownCurrencyException
     */
    public function run(): void
    {
        /** @var ProductService $productService */
        $productService = App::make(ProductService::class);
        /** @var PriceMapService $priceMapService */
        $priceMapService = App::make(PriceMapService::class);

        $language = Language::query()->where('default', false)->firstOrFail()->getKey();

        $products = Product::factory()->count(100)
            ->state(fn($sequence) => [
                'shipping_digital' => mt_rand(0, 1),
            ])
            ->create();

        $sets = ProductSet::all();

        $brands = ProductSet::factory([
            'name' => 'Brands',
            'slug' => 'brands',
        ])->make();
        $this->seo($brands, $language);
        $brands = ProductSet::factory([
            'parent_id' => $brands->getKey(),
        ])->count(4)->create();

        $brands->each(fn($set) => $this->seo($set, $language));

        $categories = ProductSet::factory([
            'name' => 'Categories',
            'slug' => 'categories',
        ])->create();
        $this->seo($categories, $language);
        $categories = ProductSet::factory([
            'parent_id' => $categories->getKey(),
        ])->count(4)->create();

        $categories->each(fn($set) => $this->seo($set, $language));

        $products->each(function ($product, $index) use ($productService, $priceMapService, $sets, $brands, $categories, $language): void {
            if (mt_rand(0, 1)) {
                $this->schemas($product, $language, $priceMapService);
            }

            $this->media($product);
            $this->sets($product, $sets);
            $this->seo($product, $language);

            if ($index >= 75) {
                $this->brands($product, $brands);
            } elseif ($index >= 50) {
                $this->categories($product, $categories);
            } elseif ($index >= 25) {
                $this->brands($product, $brands);
                $this->categories($product, $categories);
            }

            $product->refresh();
            $this->translations($product, $language);
            $product->save();

            $productService->updateMinPrices($product);
        });

        $this->setAvailability();
    }

    private function seo(Product|ProductSet $product, string $language): void
    {
        /** @var SeoMetadata $seo */
        $seo = SeoMetadata::factory()->create();
        $product->seo()->save($seo);
        $seoTranslation = SeoMetadata::factory()->definition();
        $seo->setLocale($language)->fill(Arr::only($seoTranslation, ['title', 'description', 'keywords', 'no_index']));
        $seo->fill(['published' => array_merge($seo->published, [$language])]);
        $seo->save();
    }

    private function schemas(Product $product, string $language, PriceMapService $priceMapService): void
    {
        /** @var Schema $schema */
        $schema = Schema::factory()->create();
        $schemaTranslation = Schema::factory()->definition();
        $schema->setLocale($language)->fill(Arr::only($schemaTranslation, ['name', 'description']));
        $schema->fill(['published' => array_merge($schema->published ?? [], [$language])]);
        $schema->product_id = $product->getKey();
        $schema->save();

        /** @var Item $item */
        $item = Item::factory()->create();
        $item->deposits()->saveMany(Deposit::factory()->count(mt_rand(0, 2))->make());

        Option::factory([
            'schema_id' => $schema->getKey(),
        ])->count(mt_rand(0, 4))->create()?->each(function (Option $option) use ($language, $priceMapService): void {
            $optionTranslation = Option::factory()->definition();
            $option->setLocale($language)->fill(Arr::only($optionTranslation, ['name']));
            $option->save();
            $priceMapService->updateOptionPricesForDefaultMaps($option, FakeDto::generatePricesInAllCurrencies([], 100));
        });
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

        $products->each(fn(Product $product) => $availabilityService->calculateProductAvailability($product));
    }

    private function translations(Product $product, string $language): void
    {
        $translation = Product::factory()->definition();
        $product->setLocale($language)->fill(Arr::only($translation, ['name', 'description_html', 'description_short']));
        $product->fill(['published' => array_merge($product->published, [$language])]);
    }
}
