<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration {
    public function up(): void
    {
        Schema::table('options', function (Blueprint $table): void {
            $table->integer('shipping_time')->nullable();
            $table->dateTime('shipping_date')->nullable();
        });
        Schema::table('products', function (Blueprint $table): void {
            $table->integer('shipping_time')->nullable();
            $table->dateTime('shipping_date')->nullable();
        });
        Schema::table('schemas', function (Blueprint $table): void {
            $table->integer('shipping_time')->nullable();
            $table->dateTime('shipping_date')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('options', function (Blueprint $table): void {
            $table->dropColumn('shipping_time');
            $table->dropColumn('shipping_date');
        });
        Schema::table('products', function (Blueprint $table): void {
            $table->dropColumn('shipping_time');
            $table->dropColumn('shipping_date');
        });
        Schema::table('schemas', function (Blueprint $table): void {
            $table->dropColumn('shipping_time');
            $table->dropColumn('shipping_date');
        });
    }
};
