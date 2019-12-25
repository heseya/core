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
            $table->increments('id');
            $table->string('name');
            $table->string('symbol', 128)->index();
            $table->integer('qty')->unsigned()->default(0);
            $table->smallInteger('category_id')->index()->unsigned()->nullable();
            $table->integer('photo_id')->unsigned()->index()->nullable();
            $table->timestamps();

            $table->foreign('category_id')->references('id')->on('categories')->onDelete('restrict');
            $table->foreign('photo_id')->references('id')->on('photos')->onDelete('set null');
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
