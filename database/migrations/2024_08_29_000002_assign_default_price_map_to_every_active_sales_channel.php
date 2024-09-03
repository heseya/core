<?php

use Domain\Currency\Currency;
use Domain\PriceMap\PriceMap;
use Domain\SalesChannel\Models\SalesChannel;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        $priceMap = PriceMap::find(Currency::DEFAULT->getDefaultPriceMapId());

        SalesChannel::query()
            ->whereNull('price_map_id')
            ->active()
            ->update(['price_map_id' => $priceMap->getKey()]);
    }

    public function down(): void {}
};
