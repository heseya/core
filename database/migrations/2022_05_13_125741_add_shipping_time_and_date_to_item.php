<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration {
    public function up(): void
    {
        Schema::table('deposits', function (Blueprint $table): void {
            $table->integer('shipping_time')->nullable();
            $table->dateTime('shipping_date')->nullable();
        });
        Schema::table('items', function (Blueprint $table): void {
            $table->integer('unlimited_stock_shipping_time')->nullable();
            $table->dateTime('unlimited_stock_shipping_date')->nullable();
            $table->integer('shipping_time')->nullable();
            $table->dateTime('shipping_date')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('deposits', function (Blueprint $table): void {
            $table->dropColumn('shipping_time');
            $table->dropColumn('shipping_date');
        });
        Schema::table('items', function (Blueprint $table): void {
            $table->dropColumn('unlimited_stock_shipping_time');
            $table->dropColumn('unlimited_stock_shipping_date');
            $table->dropColumn('shipping_time');
            $table->dropColumn('shipping_date');
        });
    }
};
