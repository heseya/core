<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSeoMetadataTable extends Migration
{
    public function up(): void
    {
        Schema::create('seo_metadata', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->boolean('global');
            $table->string('title')->nullable();
            $table->string('description', 1000)->nullable();
            $table->json('keywords')->nullable();
            $table->string('twitter_card')->nullable();

            $table->uuid('og_image')->nullable();

            $table->foreign('og_image')->references('id')->on('media')->onDelete('cascade');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('seo_metadata');
    }
}
