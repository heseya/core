<?php

declare(strict_types=1);

use App\Models\Option;
use App\Models\Price;
use App\Models\Product;
use Database\Seeders\PriceMapSeeder;
use Domain\Currency\Currency;
use Domain\PriceMap\PriceMap;
use Domain\PriceMap\PriceMapProductPrice;
use Domain\PriceMap\PriceMapSchemaOptionPrice;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\App;
use Ramsey\Uuid\Uuid;

return new class extends Migration
{
    public function up(): void
    {
        App::make(PriceMapSeeder::class)->run();

        foreach (Currency::cases() as $case) {
            $priceMap = PriceMap::find($case->getDefaultPriceMapId());

            if ($priceMap) {
                Product::query()->with('pricesBase')->chunkById(100, function (Collection $products) use ($priceMap) {
                    $data = [];
                    /** @var Product $product */
                    foreach ($products as $product) {
                        /** @var Price $price */
                        $price = $product->pricesBase->where('currency', $priceMap->currency->value)->first();
                        $data[] = [
                            'id' => (string) Uuid::uuid6(),
                            'price_map_id' => $priceMap->getKey(),
                            'product_id' => $product->getKey(),
                            'value' => $price?->getRawOriginal('value') ?? 0,
                            'currency' => $priceMap->currency->value,
                            'is_net' => $priceMap->is_net,
                        ];
                    }
                    PriceMapProductPrice::query()->upsert($data, ['price_map_id', 'product_id'], ['value', 'currency', 'is_net']);
                });

                Option::query()->with('prices')->chunkById(100, function (Collection $options) use ($priceMap) {
                    $data = [];
                    /** @var Option $option */
                    foreach ($options as $option) {
                        /** @var Price $price */
                        $price = $option->prices->where('currency', $priceMap->currency->value)->first();

                        $data[] = [
                            'id' => (string) Uuid::uuid6(),
                            'price_map_id' => $priceMap->getKey(),
                            'option_id' => $option->getKey(),
                            'value' => $price?->getRawOriginal('value') ?? 0,
                            'currency' => $priceMap->currency->value,
                            'is_net' => $priceMap->is_net,
                        ];
                    }
                    PriceMapSchemaOptionPrice::query()->upsert($data, ['price_map_id', 'option_id'], ['value', 'currency', 'is_net']);
                });
            }
        }
    }

    public function down(): void {}
};
