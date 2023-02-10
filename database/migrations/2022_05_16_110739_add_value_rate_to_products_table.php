<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table): void {
            $table->unsignedDecimal('vat_rate', 8, 4)->default(0);
        });

        Schema::table('order_products', function (Blueprint $table): void {
            $table->unsignedDecimal('vat_rate', 8, 4)->default(0);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::table('order_products', function (Blueprint $table): void {
            $table->dropColumn('vat_rate');
        });

        Schema::table('products', function (Blueprint $table): void {
            $table->dropColumn('vat_rate');
        });
    }
};
