<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('attribute_product_set', function (Blueprint $table) {
            $table->unsignedInteger('order')->default(0);
        });
    }

    public function down(): void
    {
        Schema::table('attribute_product_set', function (Blueprint $table) {
            $table->dropColumn('order');
        });
    }
};
