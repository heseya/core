<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ProductQuantityStep extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->float('quantity_step')->default(1);
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn('quantity_step');
        });
    }
}
