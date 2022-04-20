<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddDescriptionHtmlToProductSetsTable extends Migration
{
    public function up(): void
    {
        Schema::table('product_sets', function (Blueprint $table) {
            $table->text('description_html')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('product_sets', function (Blueprint $table) {
            $table->dropColumn('description_html');
        });
    }
}
