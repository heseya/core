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
      $table->bigIncrements('id');
      $table->string('name');
      $table->smallInteger('category')->unsigned();
      $table->timestamps();
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
