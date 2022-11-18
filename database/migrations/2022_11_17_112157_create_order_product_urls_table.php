<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('order_product_urls', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->string('name');
            $table->string('url');

            $table
                ->foreignUuid('order_product_id')
                ->references('id')
                ->on('order_products');

            $table->timestamps();
        });

        Schema::table('order_products', function (Blueprint $table) {
            $table->boolean('shipping_digital')->default(false);
            $table->boolean('is_delivered')->default(false);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('order_product_urls');

        Schema::table('order_products', function (Blueprint $table) {
            $table->dropColumn('shipping_digital');
            $table->dropColumn('is_delivered');
        });
    }
};
