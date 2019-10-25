<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateProductsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     *
     * W tej tabeli będą produkty Produkty mogą byc wyświetlane w sklepie. Każdy produkt składa się z jakiś półproduktów (towarów, minimum jednego). Np. benz składa się z łańcuszka i zawieszki. Dążymy do tego żeby dało się zmieniać łańcuszki przy kupowaniu nasyzjnika. Sold out powinnien wyświetlać się jeśli nie ma dostępnej żadnej z opcji konfiguracji.
     */
    public function up()
    {
        Schema::create('products', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name');
            $table->string('color', 6)->default('bbd0d3');
            $table->smallInteger('brand_id')->unsigned()->nullable();
            $table->smallInteger('category_id')->unsigned()->nullable();
            $table->text('description')->nullable();
            $table->timestamps();

            $table->foreign('category_id')->references('id')->on('categories')->onDelete('set null');
            $table->foreign('brand_id')->references('id')->on('brands')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('products');
    }
}
