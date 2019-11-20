<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

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
            $table->string('slug')->unique()->index();
            $table->float('price', 8, 2);
            $table->smallInteger('brand_id')->index()->unsigned();
            $table->smallInteger('category_id')->index()->unsigned();
            $table->text('description')->nullable();
            $table->boolean('public')->default(false);
            $table->timestamps();

            $table->foreign('category_id')->references('id')->on('categories')->onDelete('restrict');
            $table->foreign('brand_id')->references('id')->on('brands')->onDelete('restrict');
        });

        Schema::create('photo_product', function (Blueprint $table) {
            $table->increments('id');

            $table->integer('photo_id')->unsigned()->index();
            $table->foreign('photo_id')->references('id')->on('photos')->onDelete('cascade');

            $table->integer('product_id')->unsigned()->index();
            $table->foreign('product_id')->references('id')->on('products')->onDelete('cascade');
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
