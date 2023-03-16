<?php

use App\Models\Order;
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
        DB::table('orders')->lazyById()->each(function (object $order) {
            $moneySummary = Money::of($order->summary, 'PLN');
            $moneyShippingPrice = Money::of($order->shipping_price, 'PLN');
            $moneyShippingPriceInitial = Money::of($order->shipping_price_initial, 'PLN');
            $moneyCartTotal = Money::of($order->cart_total, 'PLN');
            $moneyCartTotalInitial = Money::of($order->cart_total_initial, 'PLN');

            $insertPrice = fn(string $type, Money $value) => DB::table('prices')->insert([
                'id' => Str::uuid(),
                'model_id' => $order->id,
                'model_type' => Order::class,
                'price_type' => $type,
                'value' => $value,
            ]);

            $insertPrice('summary', $moneySummary->getMinorAmount());
            $insertPrice('shipping_price', $moneyShippingPrice->getMinorAmount());
            $insertPrice('shipping_price_initial', $moneyShippingPriceInitial->getMinorAmount());
            $insertPrice('cart_total', $moneyCartTotal->getMinorAmount());
            $insertPrice('cart_total_initial', $moneyCartTotalInitial->getMinorAmount());

//            DB::table('prices')->insert([
//                'id' => Str::uuid(),
//                'model_id' => $order->id,
//                'model_type' => Order::class,
//                'price_type' => 'summary',
//                'value' => $moneySummary->getMinorAmount(),
//            ]);
//
//            DB::table('prices')->insert([
//                'id' => Str::uuid(),
//                'model_id' => $order->id,
//                'model_type' => Order::class,
//                'price_type' => 'shipping_price',
//                'value' => $moneyShippingPrice->getMinorAmount(),
//            ]);
//
//            DB::table('prices')->insert([
//                'id' => Str::uuid(),
//                'model_id' => $order->id,
//                'model_type' => Order::class,
//                'price_type' => 'shipping_price_initial',
//                'value' => $moneyShippingPriceInitial->getMinorAmount(),
//            ]);
//
//            DB::table('prices')->insert([
//                'id' => Str::uuid(),
//                'model_id' => $order->id,
//                'model_type' => Order::class,
//                'price_type' => 'cart_total',
//                'value' => $moneyCartTotal->getMinorAmount(),
//            ]);
//
//            DB::table('prices')->insert([
//                'id' => Str::uuid(),
//                'model_id' => $order->id,
//                'model_type' => Order::class,
//                'price_type' => 'cart_total_initial',
//                'value' => $moneyCartTotalInitial->getMinorAmount(),
//            ]);
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
