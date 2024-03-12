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
        Schema::table('order_products', function (Blueprint $table) {
            $table->string('currency', 3)->nullable();
        });

        DB::table('order_products')->lazyById()->each(function (object $orderProduct) {
            $order = DB::table('orders')->where('id', $orderProduct->order_id)->first();

            if ($order === null) {
                throw new Exception('No order found for order product');
            }

            $price = Money::of($orderProduct->price, $order->currency, roundingMode: RoundingMode::HALF_UP);
            $price_initial = Money::of($orderProduct->price_initial, $order->currency, roundingMode: RoundingMode::HALF_UP);
            $base_price = Money::of($orderProduct->base_price, $order->currency, roundingMode: RoundingMode::HALF_UP);
            $base_price_initial = Money::of($orderProduct->base_price_initial, $order->currency, roundingMode: RoundingMode::HALF_UP);

            DB::table('order_products')
                ->where('id', $orderProduct->id)
                ->update([
                    'currency' => $order->currency,
                    'price' => $price->getMinorAmount(),
                    'price_initial' => $price_initial->getMinorAmount(),
                    'base_price' => $base_price->getMinorAmount(),
                    'base_price_initial' => $base_price_initial->getMinorAmount(),
                ]);
        });

        Schema::table('order_products', function (Blueprint $table) {
            $table->string('currency', 3)->nullable(false)->change();
            $table->decimal('price', 27, 0)->change();
            $table->decimal('price_initial', 27, 0)->change();
            $table->decimal('base_price', 27, 0)->change();
            $table->decimal('base_price_initial', 27, 0)->change();
        });
    }

    public function down(): void
    {
        Schema::table('order_products', function (Blueprint $table) {
            $table->float('price', 19, 4)->change();
            $table->float('price_initial', 19, 4)->change();
            $table->float('base_price', 19, 4)->change();
            $table->float('base_price_initial', 19, 4)->change();
        });

         DB::table('order_products')->lazyById()->each(function (object $orderProduct) {
             $price = Money::ofMinor($orderProduct->price, $orderProduct->currency);
             $price_initial = Money::ofMinor($orderProduct->price_initial, $orderProduct->currency);
             $base_price = Money::ofMinor($orderProduct->base_price, $orderProduct->currency);
             $base_price_initial = Money::ofMinor($orderProduct->base_price_initial, $orderProduct->currency);

            DB::table('order_products')
                ->where('id', $orderProduct->id)
                ->update([
                    'price' => $price->getAmount(),
                    'price_initial' => $price_initial->getAmount(),
                    'base_price' => $base_price->getAmount(),
                    'base_price_initial' => $base_price_initial->getAmount(),
                ]);
        });

        Schema::table('order_products', function (Blueprint $table) {
            $table->dropColumn('currency');
        });
    }
};
