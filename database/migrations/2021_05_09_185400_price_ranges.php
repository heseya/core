<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class PriceRanges extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('price_ranges', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->float('start', 19, 4);
            $table->uuid('shipping_method_id')->index()->nullable();
            $table->timestamps();

            $table->unique(['start', 'shipping_method_id']);

            $table->foreign('shipping_method_id')->references('id')->on('shipping_methods')->onDelete('cascade');
        });

        Schema::create('prices', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->float('value', 19, 4);
            $table->uuid('price_range_id')->index()->nullable();
            $table->uuid('product_id')->index()->nullable();
            $table->uuid('schema_id')->index()->nullable();
            $table->uuid('option_id')->index()->nullable();
            $table->uuid('region_id')->index()->nullable();
            $table->timestamps();

            $table->unique(['value', 'price_range_id', 'region_id']);
            $table->unique(['value', 'product_id', 'region_id']);
            $table->unique(['value', 'schema_id', 'region_id']);
            $table->unique(['value', 'option_id', 'region_id']);

            $table->foreign('price_range_id')->references('id')->on('price_ranges')->onDelete('cascade');
            $table->foreign('product_id')->references('id')->on('products')->onDelete('cascade');
            $table->foreign('schema_id')->references('id')->on('schemas')->onDelete('cascade');
            $table->foreign('option_id')->references('id')->on('options')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('price_ranges');
        Schema::dropIfExists('prices');
    }
}
