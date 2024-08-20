<?php

namespace Database\Seeders;

use Domain\Currency\Currency;
use Domain\PriceMap\PriceMap;
use Domain\PriceMap\PriceMapService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\App;

class PriceMapSeeder extends Seeder
{
    public function run(): void
    {
        foreach (Currency::cases() as $case) {
            if (!PriceMap::find($case->getDefaultPriceMapId())) {
                $defaultMap = new PriceMap([
                    'name' => 'Default ' . $case->value,
                    'description' => 'Default ' . $case->value,
                    'currency' => $case->value,
                    'is_net' => true,
                ]);
                $defaultMap->id = $case->getDefaultPriceMapId();
                $defaultMap->save();

                /** @var PriceMapService $productService */
                $priceMapService = App::make(PriceMapService::class);
                $priceMapService->createPricesForAllMissingProductsAndSchemas($defaultMap);
            }
        }
    }
}
