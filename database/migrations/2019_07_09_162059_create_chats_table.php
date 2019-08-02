<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateChatsTable extends Migration
{
  /**
   * Run the migrations.
   *
   * @return void
   */
  public function up()
  {
    Schema::create('chats', function (Blueprint $table) {
      $table->increments('id');
      $table->integer('client_id')->unsigned()->index();
      $table->smallInteger('type');
      $table->string('system_id', 128)->nullable();
      $table->timestamps();

      $table->foreign('client_id')->references('id')->on('clients');
    });
  }

  /**
   * Reverse the migrations.
   *
   * @return void
   */
  public function down()
  {
    Schema::dropIfExists('chats');
  }
}
