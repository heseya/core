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
        Schema::table('attributes', function (Blueprint $table): void {
            $table->unsignedInteger('order')->default(0);
        });

        Schema::table('attribute_options', function (Blueprint $table): void {
            $table->unsignedInteger('order')->default(0);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('attributes', function (Blueprint $table): void {
            $table->dropColumn('order');
        });

        Schema::table('attribute_options', function (Blueprint $table): void {
            $table->dropColumn('order');
        });
    }
};
