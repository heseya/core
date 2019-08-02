<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateOrdersTable extends Migration
{
  /**
   * Run the migrations.
   *
   * @return void
   */
  public function up()
  {
    Schema::create('orders', function (Blueprint $table) {
      $table->increments('id')->uniqe();
      $table->string('code', 16)->unique();
      $table->string('email', 256);
      $table->smallInteger('payment')->default(0);
      $table->tinyInteger('payment_status')->default(0);
      $table->tinyInteger('shop_status')->default(0);
      $table->smallInteger('delivery')->default(0);
      $table->tinyInteger('delivery_status')->default(0);
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
    Schema::dropIfExists('orders');
  }
}
