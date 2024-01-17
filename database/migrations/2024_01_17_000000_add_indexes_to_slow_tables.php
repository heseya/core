<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('metadata', function (Blueprint $table) {
            $table->index(['model_id']);
        });
        Schema::table('attribute_options', function (Blueprint $table) {
            $table->index(['attribute_id']);
        });
    }

    public function down(): void
    {
        Schema::table('metadata', function (Blueprint $table) {
            $table->dropIndex(['model_id']);
        });
        Schema::table('attribute_options', function (Blueprint $table) {
            $table->dropIndex(['attribute_id']);
        });
    }
};
