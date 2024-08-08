<?php

namespace Database\Seeders;

use Domain\Currency\Currency;
use Domain\PriceMap\PriceMap;
use Domain\PriceMap\PriceMapService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\App;

class PriceMapSeeder extends Seeder
{
    const DEFAULT_MAP_UUID = '019130e4-d59b-78fb-989a-f0d4431dab7c';

    public function run(): void
    {
        if (!PriceMap::find(self::DEFAULT_MAP_UUID)) {
            $defaultMap = PriceMap::create([
                'id' => self::DEFAULT_MAP_UUID,
                'name' => 'Default',
                'description' => 'Default',
                'currency' => Currency::DEFAULT->value,
                'is_net' => true,
            ]);

            /** @var PriceMapService $productService */
            $priceMapService = App::make(PriceMapService::class);
            $priceMapService->createPricesForAllMissingProductsAndSchemas($defaultMap);
        }
    }
}
