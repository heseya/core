<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('product_sets', function (Blueprint $table): void {
            $table->dropColumn('hide_on_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('product_sets', function (Blueprint $table): void {
            $table->boolean('hide_on_index')->default(false);
        });
    }
};
