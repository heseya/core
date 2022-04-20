<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ChangeOrderTable extends Migration
{
    public function up(): void
    {
        Schema::table('order_schemas', function (Blueprint $table) {
            $table->renameColumn('price', 'price_initial');
        });

        Schema::table('order_schemas', function (Blueprint $table) {
            $table->float('price', 19, 4);
        });

        Schema::table('order_discounts', function (Blueprint $table) {
            $table->renameColumn('discount', 'value');
            $table->string('name');
            $table->string('code')->nullable();
            $table->string('target_type');
            $table->float('applied_discount', 19, 4);
        });

        Schema::table('order_products', function (Blueprint $table) {
            $table->renameColumn('price', 'price_initial');
            $table->string('name');
        });

        Schema::table('order_products', function (Blueprint $table) {
            $table->float('price', 19, 4);
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->renameColumn('shipping_price', 'shipping_price_initial');
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->float('shipping_price', 19, 4);

            $table->renameColumn('user_id', 'buyer_id');
            $table->renameColumn('user_type', 'buyer_type');
            $table->float('cart_total_initial', 19, 4)->default(0);
            $table->float('cart_total', 19, 4)->default(0);
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn('shipping_price');

            $table->renameColumn('buyer_id', 'user_id');
            $table->renameColumn('buyer_type', 'user_type');
            $table->dropColumn('cart_total_initial');
            $table->dropColumn('cart_total');
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->renameColumn('shipping_price_initial', 'shipping_price');
        });

        Schema::table('order_products', function (Blueprint $table) {
            $table->dropColumn('price');
        });

        Schema::table('order_products', function (Blueprint $table) {
            $table->renameColumn('price_initial', 'price');
            $table->dropColumn('name');
        });

        Schema::table('order_discounts', function (Blueprint $table) {
            $table->renameColumn('value', 'discount');
            $table->dropColumn('name');
            $table->dropColumn('code');
            $table->dropColumn('target_type');
            $table->dropColumn('applied_discount');
        });

        Schema::table('order_schemas', function (Blueprint $table) {
            $table->dropColumn('price');
        });

        Schema::table('order_schemas', function (Blueprint $table) {
            $table->renameColumn('price_initial', 'price');
        });
    }
}
