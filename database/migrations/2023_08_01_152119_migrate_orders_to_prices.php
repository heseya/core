<?php

declare(strict_types=1);

use App\Models\Discount;
use Brick\Math\BigDecimal;
use Brick\Math\RoundingMode;
use Brick\Money\Money;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Ramsey\Uuid\Uuid;

return new class extends Migration {
    public function up(): void
    {
        DB::table('orders')->lazyById()->each(function (object $order) {
            $cart_total_initial = Money::of($order->cart_total_initial, $order->currency, roundingMode: RoundingMode::HALF_UP);
            $cart_total = Money::of($order->cart_total, $order->currency, roundingMode: RoundingMode::HALF_UP);
            $shipping_price_initial = Money::of($order->shipping_price_initial, $order->currency, roundingMode: RoundingMode::HALF_UP);
            $shipping_price = Money::of($order->shipping_price, $order->currency, roundingMode: RoundingMode::HALF_UP);
            $summary = Money::of($order->summary, $order->currency, roundingMode: RoundingMode::HALF_UP);

            DB::table('orders')
                ->where('id', $order->id)
                ->update([
                    'cart_total_initial' => $cart_total_initial->getMinorAmount(),
                    'cart_total' => $cart_total->getMinorAmount(),
                    'shipping_price_initial' => $shipping_price_initial->getMinorAmount(),
                    'shipping_price' => $shipping_price->getMinorAmount(),
                    'summary' => $summary->getMinorAmount(),
                ]);
        });


        Schema::table('orders', function (Blueprint $table) {
            $table->decimal('cart_total_initial', 27, 0)->change();
            $table->decimal('cart_total', 27, 0)->change();
            $table->decimal('shipping_price_initial', 27, 0)->change();
            $table->decimal('shipping_price', 27, 0)->change();
            $table->decimal('summary', 27, 0)->change();
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->float('cart_total_initial', 19, 4)->change();
            $table->float('cart_total', 19, 4)->change();
            $table->float('shipping_price_initial', 19, 4)->change();
            $table->float('shipping_price', 19, 4)->change();
            $table->float('summary', 19, 4)->change();
        });

         DB::table('orders')->lazyById()->each(function (object $order) {
            $cart_total_initial = Money::ofMinor($order->cart_total_initial, $order->currency);
            $cart_total = Money::ofMinor($order->cart_total, $order->currency);
            $shipping_price_initial = Money::ofMinor($order->shipping_price_initial, $order->currency);
            $shipping_price = Money::ofMinor($order->shipping_price, $order->currency);
            $summary = Money::ofMinor($order->summary, $order->currency);

            DB::table('orders')
                ->where('id', $order->id)
                ->update([
                    'cart_total_initial' => $cart_total_initial->getAmount(),
                    'cart_total' => $cart_total->getAmount(),
                    'shipping_price_initial' => $shipping_price_initial->getAmount(),
                    'shipping_price' => $shipping_price->getAmount(),
                    'summary' => $summary->getAmount(),
                ]);
        });
    }
};
