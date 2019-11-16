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
            $table->integer('client_id')->unsigned()->index()->nullable();
            $table->string('email', 256);
            $table->smallInteger('payment')->default(0);
            $table->tinyInteger('payment_status')->default(0);
            $table->tinyInteger('shop_status')->default(0);
            $table->smallInteger('delivery')->nullable();
            $table->tinyInteger('delivery_status')->default(0);
            $table->string('delivery_tracking')->nullable();
            $table->integer('delivery_address')->unsigned()->index()->nullable();
            $table->integer('invoice_address')->unsigned()->index()->nullable();
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
