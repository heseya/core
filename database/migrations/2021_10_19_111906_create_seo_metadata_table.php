<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSeoMetadataTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('seo_metadata', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->boolean('global');
            $table->string('title')->nullable();
            $table->string('description')->nullable();
            $table->json('keywords')->nullable();
            $table->string('twitter_card')->nullable();

            $table->uuid('og_image')->nullable();

            $table->foreign('og_image')->references('id')->on('media')->onDelete('cascade');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('seo_metadata');
    }
}
