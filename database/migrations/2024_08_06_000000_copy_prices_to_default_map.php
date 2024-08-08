<?php

declare(strict_types=1);

use App\Models\Price;
use App\Models\Product;
use Database\Seeders\PriceMapSeeder;
use Domain\PriceMap\PriceMap;
use Domain\PriceMap\PriceMapProductPrice;
use Domain\PriceMap\PriceMapSchemaOptionPrice;
use Domain\ProductSchema\Models\Schema;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\App;

return new class extends Migration
{
    public function up(): void
    {
        App::make(PriceMapSeeder::class)->run();

        $priceMap = PriceMap::find(PriceMapSeeder::class);

        Product::query()->with('pricesBase')->chunk(100, function (Collection $products) use ($priceMap) {
            foreach ($products as $product) {
                /** @var Price $price */
                $price = $product->pricesBase->where('currency', $priceMap->currency->value)->first();
                PriceMapProductPrice::where(['price_map_id' => $priceMap->getKey(), 'product_id' => $product->getKey()])->update(['value' => $price->getRawOriginal('value')]);
            }
        });

        Schema::query()->with('options', 'options.prices')->chunk(100, function (Collection $schemas) use ($priceMap) {
            foreach ($schemas as $schema) {
                foreach ($schema->options as $option) {
                    /** @var Price $price */
                    $price = $option->prices->where('currency', $priceMap->currency->value)->first();
                    PriceMapSchemaOptionPrice::where(['price_map_id' => $priceMap->getKey(), 'option_id' => $option->getKey()])->update(['value' => $price->getRawOriginal('value')]);
                }
            }
        });
    }

    public function down(): void {}
};
