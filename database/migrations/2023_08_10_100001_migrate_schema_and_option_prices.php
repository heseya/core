<?php

use App\Enums\Product\ProductPriceType;
use App\Models\Option;
use App\Models\Schema;
use Brick\Money\Money;
use Domain\Currency\Currency;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::query()->lazyById()->each(function (Schema $schema) {
            $schema->prices()->create([
                'value' => $schema->price,
                'currency' => Currency::DEFAULT->value,
                'price_type' => ProductPriceType::PRICE_BASE->value,
            ]);
        });

        Option::query()->lazyById()->each(function (Option $option) {
            $option->prices()->create([
                'value' => $option->price,
                'currency' => Currency::DEFAULT->value,
                'price_type' => ProductPriceType::PRICE_BASE->value,
            ]);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::query()->lazyById()->each(function (Schema $schema) {
            $price = DB::table('prices')
                ->where('model_id', $schema->getKey())
                ->where('price_type', ProductPriceType::PRICE_BASE->value)
                ->first();

            if ($price !== null) {
                $schema->update(['price' => Money::ofMinor($price->value, 'PLN')->getAmount()]);
            }
        });

        Option::query()->lazyById()->each(function (Option $option) {
            $price = DB::table('prices')
                ->where('model_id', $option->getKey())
                ->where('price_type', ProductPriceType::PRICE_BASE->value)
                ->first();

            if ($price !== null) {
                $option->update(['price' => Money::ofMinor($price->value, 'PLN')->getAmount()]);
            }
        });
    }
};
