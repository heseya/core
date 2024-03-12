<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_banner_media', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('url')->nullable();
            $table->text('title')->nullable();
            $table->text('subtitle')->nullable();
            $table->timestamps();
        });

        Schema::create('product_banner_responsive_media', function (Blueprint $table) {
            $table
                ->foreignUuid('product_banner_media_id')
                ->references('id')
                ->on('product_banner_media')
                ->onDelete('cascade');
            $table
                ->foreignUuid('media_id')
                ->references('id')
                ->on('media')
                ->onDelete('cascade');
            $table->integer('min_screen_width');
        });

        Schema::table('products', function (Blueprint $table) {
            $table->uuid('banner_media_id')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn('banner_media_id');
        });

        Schema::dropIfExists('product_banner_responsive_media');
        Schema::dropIfExists('product_banner_media');
    }
};
