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
        Schema::create('order_product_urls', function (Blueprint $table): void {
            $table->uuid('id')->primary();

            $table->string('name');
            $table->string('url');

            $table
                ->foreignUuid('order_product_id')
                ->references('id')
                ->on('order_products');

            $table->timestamps();
        });

        Schema::table('order_products', function (Blueprint $table): void {
            $table->boolean('shipping_digital')->default(false);
            $table->boolean('is_delivered')->default(false);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('order_product_urls');

        Schema::table('order_products', function (Blueprint $table): void {
            $table->dropColumn('shipping_digital');
            $table->dropColumn('is_delivered');
        });
    }
};
