<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('attributes', function (Blueprint $table) {
            $table->text('description')->nullable()->change();
        });

        Schema::table('banner_media', function (Blueprint $table) {
            $table->text('title')->nullable()->change();
            $table->text('subtitle')->nullable()->change();
        });

        Schema::table('discounts', function (Blueprint $table) {
            $table->text('description')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('attributes', function (Blueprint $table) {
            $table->text('description')->change();
        });

        Schema::table('banner_media', function (Blueprint $table) {
            $table->text('title')->change();
            $table->text('subtitle')->change();
        });

        Schema::table('discounts', function (Blueprint $table) {
            $table->text('name')->change();
            $table->text('description')->change();
        });
    }
};
