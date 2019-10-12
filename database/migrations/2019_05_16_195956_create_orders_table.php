<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

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
            $table->integer('client_id')->unsigned()->nullable()->default(null);
            $table->string('email', 256);
            $table->smallInteger('payment')->default(0);
            $table->tinyInteger('payment_status')->default(0);
            $table->tinyInteger('shop_status')->default(0);
            $table->smallInteger('delivery')->default(0);
            $table->tinyInteger('delivery_status')->default(0);
            $table->integer('delivery_address')->unsigned()->nullable()->default(null);
            $table->integer('invoice_address')->unsigned()->nullable()->default(null);
            $table->timestamps();

            // Relations
            $table->foreign('client_id')->references('id')->on('clients')->onDelete('set null');
            $table->foreign('delivery_address')->references('id')->on('addresses')->onDelete('restrict');
            $table->foreign('invoice_address')->references('id')->on('addresses')->onDelete('restrict');
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
