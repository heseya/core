<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('banner_media', function (Blueprint $table) {
            $table->text('title')->change();
            $table->text('subtitle')->change();
            $table->text('published')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('banner_media', function (Blueprint $table) {
            $table->string('title')->change();
            $table->string('subtitle')->change();
            $table->dropColumn('published');
        });
    }
};
