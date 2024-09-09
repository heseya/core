<?php

use App\Models\Option;
use App\Models\Product;
use Domain\PriceMap\PriceMap;
use Domain\PriceMap\PriceMapProductPrice;
use Domain\PriceMap\PriceMapSchemaOptionPrice;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Migrations\Migration;
use Ramsey\Uuid\Uuid;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $priceMapsById = PriceMap::query()->get()->keyBy('id');

        $query = Option::query();
        /** @var PriceMap $priceMap */
        foreach ($priceMapsById as $priceMapId => $priceMap) {
            $query->orWhereDoesntHave(
                'mapPrices',
                fn($q) => $q->where('price_map_id', '=', $priceMapId)->where('currency', '=', $priceMap->currency->value)
            );
        }
        $query->chunkById(100, function (Collection $options) use ($priceMapsById) {
            $data = [];
            foreach ($options as $option) {
                $existingPriceMaps = [];
                /** @var PriceMapSchemaOptionPrice $optionMapPrice */
                foreach ($option->mapPrices as $optionMapPrice) {
                    if ($priceMapsById->get($optionMapPrice->price_map_id)->currency === $optionMapPrice->currency) {
                        $existingPriceMaps[] = $optionMapPrice->price_map_id;
                    }
                }
                /** @var PriceMap $priceMap */
                foreach ($priceMapsById as $priceMapId => $priceMap) {
                    if (!in_array($priceMap->getKey(), $existingPriceMaps)) {
                        $data[] = [
                            'id' => Uuid::uuid6(),
                            'price_map_id' => $priceMap->getKey(),
                            'option_id' => $option->getKey(),
                            'value' => 0,
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
                fn($q) => $q->where('price_map_id', '=', $priceMapId)->where('currency', '=', $priceMap->currency->value)
            );
        }
        $query->chunkById(100, function (Collection $products) use ($priceMapsById) {
            $data = [];
            foreach ($products as $product) {
                $existingPriceMaps = [];
                /** @var PriceMapProductPrice $productMapPrice */
                foreach ($product->mapPrices as $productMapPrice) {
                    if ($priceMapsById->get($productMapPrice->price_map_id)->currency === $productMapPrice->currency) {
                        $existingPriceMaps[] = $productMapPrice->price_map_id;
                    }
                }
                /** @var PriceMap $priceMap */
                foreach ($priceMapsById as $priceMapId => $priceMap) {
                    if (!in_array($priceMap->getKey(), $existingPriceMaps)) {
                        $data[] = [
                            'id' => Uuid::uuid6(),
                            'price_map_id' => $priceMap->getKey(),
                            'product_id' => $product->getKey(),
                            'value' => 0,
                            'currency' => $priceMap->currency->value,
                            'is_net' => $priceMap->is_net,
                        ];
                    }
                }
            }
            PriceMapProductPrice::query()->upsert($data, ['price_map_id', 'product_id'], ['value', 'currency', 'is_net']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
