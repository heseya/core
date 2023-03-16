<?php

use App\Models\PriceRange;
use Brick\Money\Money;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('price_ranges', function (Blueprint $table) {
            $table->decimal('start', 27, 0)->change();
            $table->decimal('value', 27, 0);
        });

        DB::table('price_ranges')->lazyById()->each(function (object $priceRange) {
            $price = DB::table('prices')
                ->where('model_id', $priceRange->id)
                ->first();

            $money = Money::of($price->value, 'PLN');

            DB::table('price_ranges')
                ->where('id', $priceRange->id)
                ->update(['value' => $money->getMinorAmount()]);
        });

        Schema::drop('prices');
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::create('prices', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('model_id')->index();
            $table->string('model_type')->index();
            $table->float('value', 19, 4);
            $table->timestamps();

            $table->unique(['value', 'model_type', 'model_id']);
        });

        DB::table('price_ranges')->lazyById()->each(function (object $priceRange) {
            $money = Money::ofMinor($priceRange->value, 'PLN');

            DB::table('prices')->insert([
                'id' => Str::uuid(),
                'model_id' => $priceRange->id,
                'model_type' => PriceRange::class,
                'value' => $money->getAmount(),
            ]);
        });

        Schema::table('price_ranges', function (Blueprint $table) {
            $table->float('start', 19, 4)->change();
            $table->dropColumn('value');
        });
    }
};
