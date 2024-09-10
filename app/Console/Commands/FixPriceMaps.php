<?php

namespace App\Console\Commands;

use App\Models\Option;
use App\Models\Product;
use App\Services\ProductService;
use Domain\PriceMap\PriceMap;
use Domain\PriceMap\PriceMapProductPrice;
use Domain\PriceMap\PriceMapSchemaOptionPrice;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Collection;
use Ramsey\Uuid\Uuid;

class FixPriceMaps extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'price-maps:fix';
    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fix price maps';

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        $priceMapsById = PriceMap::query()->get()->keyBy('id');

        $query = Option::query();
        /** @var PriceMap $priceMap */
        foreach ($priceMapsById as $priceMapId => $priceMap) {
            $query->orWhereDoesntHave(
                'mapPrices',
                fn ($q) => $q->where('price_map_id', '=', $priceMapId)->where('currency', '=', $priceMap->currency->value),
            );
        }
        $query->chunkById(100, function (Collection $options) use ($priceMapsById): void {
            $data = [];
            /** @var Option $option */
            foreach ($options as $option) {
                $existingPriceMaps = [];
                $valueForPriceMap = [];
                $valueForCurrency = [];
                /** @var PriceMapSchemaOptionPrice $optionMapPrice */
                foreach ($option->mapPrices as $optionMapPrice) {
                    $valueForPriceMap[$optionMapPrice->price_map_id] = $optionMapPrice->value;
                    if ($priceMapsById->get($optionMapPrice->price_map_id)?->currency === $optionMapPrice->currency) {
                        $existingPriceMaps[] = $optionMapPrice->price_map_id;
                        $valueForCurrency[$optionMapPrice->currency->value] = $optionMapPrice->value;
                    } else {
                        $valueForCurrency[$optionMapPrice->currency->value] ??= $optionMapPrice->value;
                    }
                }
                /** @var PriceMap $priceMap */
                foreach ($priceMapsById as $priceMapId => $priceMap) {
                    if (!in_array($priceMap->getKey(), $existingPriceMaps)) {
                        $data[] = [
                            'id' => (string) Uuid::uuid6(),
                            'price_map_id' => $priceMap->getKey(),
                            'option_id' => $option->getKey(),
                            'value' => $valueForPriceMap[$priceMap->getKey()] ?? $valueForCurrency[$priceMap->currency->value] ?? 0,
                            'currency' => $priceMap->currency->value,
                            'is_net' => $priceMap->is_net,
                        ];
                    }
                }
            }
            PriceMapSchemaOptionPrice::query()->upsert($data, ['price_map_id', 'option_id'], ['value', 'currency', 'is_net']);
        });

        $query = Product::query();
        /** @var PriceMap $priceMap */
        foreach ($priceMapsById as $priceMapId => $priceMap) {
            $query->orWhereDoesntHave(
                'mapPrices',
                fn ($q) => $q->where('price_map_id', '=', $priceMapId)->where('currency', '=', $priceMap->currency->value),
            );
        }
        $query->chunkById(100, function (Collection $products) use ($priceMapsById): void {
            $data = [];
            /** @var Product $product */
            foreach ($products as $product) {
                $existingPriceMaps = [];
                $valueForPriceMap = [];
                $valueForCurrency = [];
                /** @var PriceMapProductPrice $productMapPrice */
                foreach ($product->mapPrices as $productMapPrice) {
                    $valueForPriceMap[$productMapPrice->price_map_id] = $productMapPrice->value;
                    if ($priceMapsById->get($productMapPrice->price_map_id)?->currency === $productMapPrice->currency) {
                        $existingPriceMaps[] = $productMapPrice->price_map_id;
                        $valueForCurrency[$productMapPrice->currency->value] = $productMapPrice->value;
                    } else {
                        $valueForCurrency[$productMapPrice->currency->value] ??= $productMapPrice->value;
                    }
                }
                /** @var PriceMap $priceMap */
                foreach ($priceMapsById as $priceMapId => $priceMap) {
                    if (!in_array($priceMap->getKey(), $existingPriceMaps)) {
                        $data[] = [
                            'id' => (string) Uuid::uuid6(),
                            'price_map_id' => $priceMap->getKey(),
                            'product_id' => $product->getKey(),
                            'value' => $valueForPriceMap[$priceMap->getKey()] ?? $valueForCurrency[$priceMap->currency->value] ?? 0,
                            'currency' => $priceMap->currency->value,
                            'is_net' => $priceMap->is_net,
                        ];
                    }
                }
            }
            PriceMapProductPrice::query()->upsert($data, ['price_map_id', 'product_id'], ['value', 'currency', 'is_net']);
        });

        $productService = app(ProductService::class);
        Product::query()->chunkById(100, fn (Collection $products) => $products->each(fn (Product $product) => $productService->updateMinPrices($product))); // @phpstan-ignore-line
    }
}
