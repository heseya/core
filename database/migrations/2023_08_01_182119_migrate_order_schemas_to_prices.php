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
        Schema::table('order_schemas', function (Blueprint $table) {
            $table->string('currency', 3)->nullable();
        });

        DB::table('order_schemas')->lazyById()->each(function (object $orderSchema) {
            $orderProduct = DB::table('order_products')->where('id', $orderSchema->order_product_id)->first();

            if ($orderProduct === null) {
                throw new Exception('No order found for order product');
            }

            $price = Money::of($orderSchema->price, $orderProduct->currency, roundingMode: RoundingMode::HALF_UP);
            $price_initial = Money::of($orderSchema->price_initial, $orderProduct->currency, roundingMode: RoundingMode::HALF_UP);

            DB::table('order_schemas')
                ->where('id', $orderSchema->id)
                ->update([
                    'currency' => $orderProduct->currency,
                    'price' => $price->getMinorAmount(),
                    'price_initial' => $price_initial->getMinorAmount(),
                ]);
        });

        Schema::table('order_schemas', function (Blueprint $table) {
            $table->string('currency', 3)->nullable(false)->change();
            $table->decimal('price', 27, 0)->change();
            $table->decimal('price_initial', 27, 0)->change();
        });
    }

    public function down(): void
    {
        Schema::table('order_schemas', function (Blueprint $table) {
            $table->float('price', 19, 4)->change();
            $table->float('price_initial', 19, 4)->change();
        });

         DB::table('order_schemas')->lazyById()->each(function (object $orderSchema) {
             $price = Money::ofMinor($orderSchema->price, $orderSchema->currency);
             $price_initial = Money::ofMinor($orderSchema->price_initial, $orderSchema->currency);

            DB::table('order_schemas')
                ->where('id', $orderSchema->id)
                ->update([
                    'price' => $price->getAmount(),
                    'price_initial' => $price_initial->getAmount(),
                ]);
        });

        Schema::table('order_schemas', function (Blueprint $table) {
            $table->dropColumn('currency');
        });
    }
};
