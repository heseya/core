<?php

declare(strict_types=1);

use Domain\PriceMap\PriceMap;
use Domain\PriceMap\PriceMapProductPrice;
use Domain\PriceMap\PriceMapSchemaOptionPrice;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        PriceMapProductPrice::whereNotIn('price_map_id', PriceMap::query()->pluck('id'))->delete();
        PriceMapSchemaOptionPrice::whereNotIn('price_map_id', PriceMap::query()->pluck('id'))->delete();

        Schema::table('price_map_product_prices', function (Blueprint $table): void {
            $table->foreign('price_map_id')->references('id')->on('price_maps')->cascadeOnDelete();
        });

        Schema::table('price_map_schema_option_prices', function (Blueprint $table): void {
            $table->foreign('price_map_id')->references('id')->on('price_maps')->cascadeOnDelete();
        });
    }

    public function down(): void {}
};
