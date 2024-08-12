<?php

declare(strict_types=1);

use Domain\Currency\Currency;
use Domain\PriceMap\PriceMap;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        PriceMap::where(['id' => '019130e4-d59b-78fb-989a-f0d4431dapln'])->update([
            'id' => Currency::DEFAULT_PRICE_MAP_PLN,
            'name' => 'Default PLN',
        ]);
        PriceMap::where(['id' => '019130e4-d59b-78fb-989a-f0d4431dagbp'])->update([
            'id' => Currency::DEFAULT_PRICE_MAP_GBP,
            'name' => 'Default GBP',
        ]);
        PriceMap::where(['id' => '019130e4-d59b-78fb-989a-f0d4431daeur'])->update([
            'id' => Currency::DEFAULT_PRICE_MAP_EUR,
            'name' => 'Default EUR',
        ]);
        PriceMap::where(['id' => '019130e4-d59b-78fb-989a-f0d4431daczk'])->update([
            'id' => Currency::DEFAULT_PRICE_MAP_CZK,
            'name' => 'Default CZK',
        ]);
        PriceMap::where(['id' => '019130e4-d59b-78fb-989a-f0d4431dabgn'])->update([
            'id' => Currency::DEFAULT_PRICE_MAP_BGN,
            'name' => 'Default BGN',
        ]);
    }

    public function down(): void {}
};
