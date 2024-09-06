<?php

use App\Models\Option;
use Domain\PriceMap\PriceMap;
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
        $priceMaps = PriceMap::query()->get();

        $query = Option::query();
        /** @var PriceMap $priceMap */
        foreach ($priceMaps as $priceMap) {
            $query->orWhereDoesntHave('mapPrices', fn ($q) => $q->where('price_map_id', '=', $priceMap->getKey()));
        }

        $query->chunkById(100, function (Collection $options) use ($priceMaps) {
            $options->each(function (Option $option) use ($priceMaps) {
                $existingPrices = $option->mapPrices()->pluck('price_map_id')->toArray();
                $data = [];
                /** @var PriceMap $priceMap */
                foreach ($priceMaps as $priceMap) {
                    if (!in_array($priceMap->getKey(), $existingPrices)) {
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

                PriceMapSchemaOptionPrice::query()->upsert($data, ['price_map_id', 'option_id']);
            });
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
