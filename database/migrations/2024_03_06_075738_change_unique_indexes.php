<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('discounts', function (Blueprint $table) {
            $table->dropUnique('discounts_slug_unique');

            $table->unique(['slug', 'deleted_at']);
        });

        Schema::table('items', function (Blueprint $table) {
            $table->dropUnique('items_sku_unique');

            $table->unique(['sku', 'deleted_at']);
        });

        Schema::table('products', function (Blueprint $table) {
            $table->dropUnique('products_slug_unique');

            $table->unique(['slug', 'deleted_at']);
        });
    }

    public function down(): void
    {
        Schema::table('discounts', function (Blueprint $table) {
            $table->dropUnique('discounts_slug_deleted_at_unique');

            $table->unique('slug');
        });

        Schema::table('items', function (Blueprint $table) {
            $table->dropUnique('items_sku_deleted_at_unique');

            $table->unique('sku');
        });

        Schema::table('products', function (Blueprint $table) {
            $table->dropUnique('products_slug_deleted_at_unique');

            $table->unique('slug');
        });
    }
};
