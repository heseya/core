<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class RemoveBrandAndCategoryIdFromProducts extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropForeign('products_brand_id_foreign');
            $table->dropForeign('products_category_id_foreign');

            $table->dropColumn('brand_id');
            $table->dropColumn('category_id');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->uuid('brand_id')->index()->nullable();
            $table->uuid('category_id')->index()->nullable();

            $table->foreign('brand_id')->references('id')
                ->on('product_sets')->onDelete('set null');
            $table->foreign('category_id')->references('id')
                ->on('product_sets')->onDelete('set null');
        });
    }
}
