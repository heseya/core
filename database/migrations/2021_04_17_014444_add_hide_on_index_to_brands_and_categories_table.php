<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddHideOnIndexToBrandsAndCategoriesTable extends Migration
{
    public function up(): void
    {
        Schema::table('brands', function (Blueprint $table) {
            $table->boolean('hide_on_index')->default(false);
        });

        Schema::table('categories', function (Blueprint $table) {
            $table->boolean('hide_on_index')->default(false);
        });
    }

    public function down(): void
    {
        Schema::table('brands', function (Blueprint $table) {
            $table->dropColumn('hide_on_index');
        });

        Schema::table('categories', function (Blueprint $table) {
            $table->dropColumn('hide_on_index');
        });
    }
}
