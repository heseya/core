<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddOrder extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('categories', function (Blueprint $table) {
            $table->unsignedTinyInteger('order')->default(0);
        });

        Schema::table('brands', function (Blueprint $table) {
            $table->unsignedTinyInteger('order')->default(0);
        });

        Schema::table('product_schemas', function (Blueprint $table) {
            $table->unsignedTinyInteger('order')->default(0);
        });

        Schema::table('shipping_methods', function (Blueprint $table) {
            $table->unsignedTinyInteger('order')->default(0);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('categories', function (Blueprint $table) {
            $table->dropColumn('order');
        });

        Schema::table('brands', function (Blueprint $table) {
            $table->dropColumn('order');
        });

        Schema::table('product_schemas', function (Blueprint $table) {
            $table->dropColumn('order');
        });

        Schema::table('shipping_methods', function (Blueprint $table) {
            $table->dropColumn('order');
        });
    }
}
