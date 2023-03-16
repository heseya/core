<?php

use App\Models\OrderProduct;
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
        DB::table('order_products')->lazyById()->each(function (object $orderProduct) {
            $moneyPrice = Money::of($orderProduct->price, 'PLN');
            $moneyPriceInitial = Money::of($orderProduct->price_initial, 'PLN');
            $moneyBasePrice = Money::of($orderProduct->base_price, 'PLN');
            $moneyBasePriceInitial = Money::of($orderProduct->base_price_initial, 'PLN');

            DB::table('prices')->insert([
                'id' => Str::uuid(),
                'model_id' => $orderProduct->id,
                'model_type' => OrderProduct::class,
                'price_type' => 'price',
                'value' => $moneyPrice->getMinorAmount(),
            ]);

            DB::table('prices')->insert([
                'id' => Str::uuid(),
                'model_id' => $orderProduct->id,
                'model_type' => OrderProduct::class,
                'price_type' => 'price_initial',
                'value' => $moneyPriceInitial->getMinorAmount(),
            ]);

            DB::table('prices')->insert([
                'id' => Str::uuid(),
                'model_id' => $orderProduct->id,
                'model_type' => OrderProduct::class,
                'price_type' => 'base_price',
                'value' => $moneyBasePrice->getMinorAmount(),
            ]);

            DB::table('prices')->insert([
                'id' => Str::uuid(),
                'model_id' => $orderProduct->id,
                'model_type' => OrderProduct::class,
                'price_type' => 'base_price_initial',
                'value' => $moneyBasePriceInitial->getMinorAmount(),
            ]);
        });

        Schema::table('order_products', function (Blueprint $table) {
            $table->dropColumn('price');
            $table->dropColumn('price_initial');
            $table->dropColumn('base_price');
            $table->dropColumn('base_price_initial');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('order_products', function (Blueprint $table) {
            $table->double('price', 19, 4);
            $table->double('price_initial', 19, 4);
            $table->double('base_price', 19, 4)->default(0);
            $table->double('base_price_initial', 19, 4)->default(0);
        });

        DB::table('order_products')->lazyById()->each(function (object $orderProduct) {
            $price = DB::table('prices')
                ->where('model_id', $orderProduct->id)
                ->where('price_type', 'price')
                ->first();

            $priceInitial = DB::table('prices')
                ->where('model_id', $orderProduct->id)
                ->where('price_type', 'price_initial')
                ->first();

            $basePrice = DB::table('prices')
                ->where('model_id', $orderProduct->id)
                ->where('price_type', 'base_price')
                ->first();

            $basePriceInitial = DB::table('prices')
                ->where('model_id', $orderProduct->id)
                ->where('price_type', 'base_price_initial')
                ->first();

            $moneyPrice = Money::of($price->value, 'PLN');
            $moneyPriceInitial = Money::of($priceInitial->value, 'PLN');
            $moneyBasePrice = Money::of($basePrice->value, 'PLN');
            $moneyBasePriceInitial = Money::of($basePriceInitial->value, 'PLN');

            DB::table('options')
                ->where('id', $orderProduct->id)
                ->update([
                    'price' => $moneyPrice->getAmount(),
                    'price_initial' => $moneyPriceInitial->getAmount(),
                    'base_price' => $moneyBasePrice->getAmount(),
                    'base_price_initial' => $moneyBasePriceInitial->getAmount(),
                ]);
        });
    }
};
