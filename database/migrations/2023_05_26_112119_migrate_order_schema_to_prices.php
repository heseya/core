<?php

use App\Models\OrderSchema;
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
        DB::table('order_schemas')->lazyById()->each(function (object $orderSchema) {
            $moneyPrice = Money::of($orderSchema->price, 'PLN');
            $moneyPriceInitial = Money::of($orderSchema->price_initial, 'PLN');

            DB::table('prices')->insert([
                'id' => Str::uuid(),
                'model_id' => $orderSchema->id,
                'model_type' => OrderSchema::class,
                'price_type' => 'price',
                'value' => $moneyPrice->getMinorAmount(),
            ]);

            DB::table('prices')->insert([
                'id' => Str::uuid(),
                'model_id' => $orderSchema->id,
                'model_type' => OrderSchema::class,
                'price_type' => 'price_initial',
                'value' => $moneyPriceInitial->getMinorAmount(),
            ]);
        });

        Schema::table('order_schemas', function (Blueprint $table) {
            $table->dropColumn('price');
            $table->dropColumn('price_initial');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('order_schemas', function (Blueprint $table) {
            $table->double('price', 19, 4);
            $table->double('price_initial', 19, 4);
        });

        DB::table('order_schemas')->lazyById()->each(function (object $orderSchema) {
            $getPrice = fn(string $type) => DB::table('prices')
                ->where('model_id', $orderSchema->id)
                ->where('price_type', $type)
                ->first();

            $price = $getPrice('price');
            $priceInitial = $getPrice('price_initial');

            $moneyPrice = Money::of($price->value, 'PLN');
            $moneyPriceInitial = Money::of($priceInitial->value, 'PLN');

            DB::table('options')
                ->where('id', $orderSchema->id)
                ->update([
                    'price' => $moneyPrice->getAmount(),
                    'price_initial' => $moneyPriceInitial->getAmount(),
                ]);
        });
    }
};
