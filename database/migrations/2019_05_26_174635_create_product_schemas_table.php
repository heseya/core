<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateProductSchemasTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('product_schemas', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('product_id')->unsigned()->index();
            $table->string('name');
            $table->boolean('required')->default(0);
            $table->timestamps();
        });

        Schema::create('product_schema_item', function (Blueprint $table) {
            $table->increments('id');
            $table->float('extraPrice', 8, 2);

            $table->integer('product_schema_id')->unsigned()->index();
            $table->foreign('product_schema_id')->references('id')->on('product_schemas')->onDelete('cascade');

            $table->integer('item_id')->unsigned()->index();
            $table->foreign('item_id')->references('id')->on('items')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('product_schemas');
        Schema::dropIfExists('product_schema_item');
    }
}
