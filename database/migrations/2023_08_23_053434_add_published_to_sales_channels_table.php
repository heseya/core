<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('sales_channels', function (Blueprint $table) {
            $table->text('published')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('sales_channels', function (Blueprint $table) {
            $table->dropColumn('published');
        });
    }
};
