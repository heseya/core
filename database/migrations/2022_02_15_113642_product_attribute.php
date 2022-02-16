<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ProductAttribute extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('product_attribute', function (Blueprint $table) {
            $table->foreignUuid('product_id')
                ->index()
                ->references('id')
                ->on('products')
                ->onDelete('cascade');

            $table->foreignUuid('attribute_id')
                ->index()
                ->references('id')
                ->on('attributes')
                ->onDelete('cascade');

            $table->foreignUuid('option_id')
                ->index()
                ->references('id')
                ->on('attribute_options')
                ->onDelete('cascade');

            $table->primary(['product_id', 'attribute_id']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('product_attribute');
    }
}
