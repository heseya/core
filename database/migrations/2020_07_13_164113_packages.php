<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class Packages extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('package_templates', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name');
            $table->float('weight');
            $table->integer('width');
            $table->integer('height');
            $table->integer('depth');
            $table->timestamps();
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->string('shipping_number')->nullable();
        });
    }
}
