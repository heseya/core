<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('product_sets', function (Blueprint $table) {
            $table->dropUnique('product_sets_slug_unique');

            $table->unique(['slug', 'deleted_at']);
        });
    }

    public function down(): void
    {
        Schema::table('product_sets', function (Blueprint $table) {
            $table->dropUnique('product_sets_slug_deleted_at_unique');

            $table->unique('slug');
        });
    }
};
