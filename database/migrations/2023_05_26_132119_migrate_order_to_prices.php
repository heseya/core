<?php

use App\Models\Order;
use Brick\Math\BigDecimal;
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

            $insertPrice = fn(string $type, BigDecimal $value) => DB::table('prices')->insert([
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
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn('summary');
            $table->dropColumn('shipping_price');
            $table->dropColumn('shipping_price_initial');
            $table->dropColumn('cart_total');
            $table->dropColumn('cart_total_initial');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->double('summary', 19, 4)->default(0);
            $table->double('shipping_price', 19, 4);
            $table->double('shipping_price_initial', 19, 4);
            $table->double('cart_total', 19, 4)->default(0);
            $table->double('cart_total_initial', 19, 4)->default(0);
        });

        DB::table('orders')->lazyById()->each(function (object $order) {
            $getPrice = fn(string $type) => DB::table('prices')
                ->where('model_id', $order->id)
                ->where('price_type', $type)
                ->first();

            $summary = $getPrice('summary');
            $shippingPrice = $getPrice('shipping_price');
            $shippingPriceInitial = $getPrice('shipping_price_initial');
            $cartTotal = $getPrice('cart_total');
            $cartTotalInitial = $getPrice('cart_total_initial');

            $moneySummary = Money::of($summary->value, 'PLN');
            $moneyShippingPrice = Money::of($shippingPrice->value, 'PLN');
            $moneyShippingPriceInitial = Money::of($shippingPriceInitial->value, 'PLN');
            $moneyCartTotal = Money::of($cartTotal->value, 'PLN');
            $moneyCartTotalInitial = Money::of($cartTotalInitial->value, 'PLN');

            DB::table('options')
                ->where('id', $order->id)
                ->update([
                    'summary' => $moneySummary->getAmount(),
                    'shipping_price' => $moneyShippingPrice->getAmount(),
                    'shipping_price_initial' => $moneyShippingPriceInitial->getAmount(),
                    'cart_total' => $moneyCartTotal->getAmount(),
                    'cart_total_initial' => $moneyCartTotalInitial->getAmount(),
                ]);
        });
    }
};
