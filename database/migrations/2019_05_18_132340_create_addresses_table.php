<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateAddressesTable extends Migration
{
  /**
   * Run the migrations.
   *
   * @return void
   */
  public function up()
  {
    Schema::create('addresses', function (Blueprint $table) {
      $table->bigIncrements('id');
      $table->bigInteger('order_id')->unsigned()->index();
      $table->string('name');
      $table->string('address');
      $table->string('zip', 16);
      $table->string('city');
      $table->string('country', 2);
      $table->timestamps();

      // Relations
      $table->foreign('order_id')->references('id')->on('orders')->onDelete('cascade');
    });
  }

  /**
   * Reverse the migrations.
   *
   * @return void
   */
  public function down()
  {
    Schema::dropIfExists('addresses');
  }
}
