<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddHideOnIndexToBrandsAndCategoriesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('brands', function (Blueprint $table) {
            $table->boolean('hide_on_index')->default(false);
        });

        Schema::table('categories', function (Blueprint $table) {
            $table->boolean('hide_on_index')->default(false);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('brands', function (Blueprint $table) {
            $table->dropColumn('hide_on_index');
        });

        Schema::table('categories', function (Blueprint $table) {
            $table->dropColumn('hide_on_index');
        });
    }
}
