<?php

declare(strict_types=1);

use Domain\Currency\Currency;
use Domain\PriceMap\PriceMap;
use Domain\PriceMap\PriceMapProductPrice;
use Domain\PriceMap\PriceMapSchemaOptionPrice;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private array $map = [
        '019130e4-d59b-78fb-989a-f0d4431dapln' => [
            'id' => Currency::DEFAULT_PRICE_MAP_PLN,
            'name' => 'Default PLN'
        ],
        '019130e4-d59b-78fb-989a-f0d4431dagbp' => [
            'id' => Currency::DEFAULT_PRICE_MAP_GBP,
            'name' => 'Default GBP'
        ],
        '019130e4-d59b-78fb-989a-f0d4431daeur' => [
            'id' => Currency::DEFAULT_PRICE_MAP_EUR,
            'name' => 'Default EUR'
        ],
        '019130e4-d59b-78fb-989a-f0d4431daczk' => [
            'id' => Currency::DEFAULT_PRICE_MAP_CZK,
            'name' => 'Default CZK'
        ],
        '019130e4-d59b-78fb-989a-f0d4431dabgn' => [
            'id' => Currency::DEFAULT_PRICE_MAP_BGN,
            'name' => 'Default BGN'
        ],
    ];

    public function up(): void
    {
        Schema::disableForeignKeyConstraints();
        foreach ($this->map as $old_id => $new) {
            PriceMap::where(['id' => $old_id])->update([
                'id' => $new['id'],
                'name' => $new['name'],
            ]);
            PriceMapProductPrice::where(['price_map_id' => $old_id])->update(['price_map_id' => $new['id']]);
            PriceMapSchemaOptionPrice::where(['price_map_id' => $old_id])->update(['price_map_id' => $new['id']]);
        }
        Schema::enableForeignKeyConstraints();
    }

    public function down(): void {}
};
