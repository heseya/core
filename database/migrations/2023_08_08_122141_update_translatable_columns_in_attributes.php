<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('attributes', function (Blueprint $table) {
            $table->text('name')->change();
            $table->text('published')->nullable();
        });

        Schema::table('attribute_options', function (Blueprint $table) {
            $table->text('name')->change();
            $table->text('published')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('attributes', function (Blueprint $table) {
            $table->string('name')->change();
            $table->dropColumn('published');
        });

        Schema::table('attribute_options', function (Blueprint $table) {
            $table->string('name')->change();
            $table->dropColumn('published');
        });
    }
};
