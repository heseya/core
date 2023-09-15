<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('shipping_methods', function (Blueprint $table): void {
            $table->boolean('is_product_blocklist')->default(false);
        });

        Schema::create('shipping_method_product', function (Blueprint $table): void {
            $table->uuid('shipping_method_id')->index();
            $table->uuid('product_id')->index();
            $table->primary(['shipping_method_id', 'product_id']);
            $table->foreign('shipping_method_id')->references('id')
                ->on('shipping_methods')->onDelete('cascade');
            $table->foreign('product_id')->references('id')
                ->on('products')->onDelete('cascade');
        });

        Schema::create('shipping_method_product_set', function (Blueprint $table): void {
            $table->uuid('shipping_method_id')->index();
            $table->uuid('product_set_id')->index();
            $table->primary(['shipping_method_id', 'product_set_id']);
            $table->foreign('shipping_method_id')->references('id')
                ->on('shipping_methods')->onDelete('cascade');
            $table->foreign('product_set_id')->references('id')
                ->on('product_sets')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('shipping_methods', function (Blueprint $table): void {
            $table->dropColumn('is_blocklist');
        });

        Schema::dropIfExists('shipping_method_product');
        Schema::dropIfExists('shipping_method_product_set');
    }
};
