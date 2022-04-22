<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddOrderToProductMediaTable extends Migration
{
    public function up(): void
    {
        Schema::table('product_media', function (Blueprint $table): void {
            $table->unsignedTinyInteger('order')->default(0);
        });
    }

    public function down(): void
    {
        Schema::table('product_media', function (Blueprint $table): void {
            $table->dropColumn('order');
        });
    }
}
