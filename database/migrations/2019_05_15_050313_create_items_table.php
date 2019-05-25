<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateItemsTable extends Migration
{
  /**
   * Run the migrations.
   *
   * @return void
   * 
   * W tabeli Items będą poszczególne przedmioty w magazynie.
   */
  public function up()
  {
    Schema::create('items', function (Blueprint $table) {
      $table->bigIncrements('id');
      $table->integer('product_id')->unsigned();
      $table->string('size', 128);
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
    Schema::dropIfExists('items');
  }
}
