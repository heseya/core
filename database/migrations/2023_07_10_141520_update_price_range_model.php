<?php

use App\Models\PriceRange;
use Brick\Math\RoundingMode;
use Brick\Money\Money;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('price_ranges', function (Blueprint $table): void {
            $table->decimal('start', 27, 0)->change();
            $table->decimal('value', 27, 0);
            $table->string('currency', 3);

            $table->dropUnique(['start', 'shipping_method_id']);
            $table->unique(['start', 'currency', 'shipping_method_id']);
        });

        DB::table('price_ranges')->lazyById()->each(function (object $priceRange): void {
            $price = DB::table('prices')
                ->where('model_id', $priceRange->id)
                ->first();

            $start = Money::of($priceRange->start, 'PLN', roundingMode: RoundingMode::HALF_UP);
            $money = Money::of($price->value, 'PLN', roundingMode: RoundingMode::HALF_UP);

            DB::table('price_ranges')
                ->where('id', $priceRange->id)
                ->update([
                    'start' => $start->getMinorAmount(),
                    'value' => $money->getMinorAmount(),
                    'currency' => $money->getCurrency()->getCurrencyCode(),
                ]);
        });

        Schema::drop('prices');
    }

    public function down(): void
    {
        Schema::create('prices', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('model_id')->index();
            $table->string('model_type')->index();
            $table->float('value', 19, 4);
            $table->timestamps();

            $table->unique(['value', 'model_type', 'model_id']);
        });

        DB::table('price_ranges')->lazyById()->each(function (object $priceRange): void {
            $money = Money::ofMinor($priceRange->value, 'PLN');

            DB::table('prices')->insert([
                'id' => Str::uuid(),
                'model_id' => $priceRange->id,
                'model_type' => PriceRange::class,
                'value' => $money->getAmount(),
            ]);
        });

        Schema::table('price_ranges', function (Blueprint $table): void {
            $table->float('start', 19, 4)->change();
            $table->dropColumn('value');
            $table->dropColumn('currency');

            $table->dropUnique(['start', 'currency', 'shipping_method_id']);
            $table->unique(['start', 'shipping_method_id']);
        });
    }
};
