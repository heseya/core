<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CrateTagsTable extends Migration
{
    public function up(): void
    {
        Schema::create('tags', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name', 30);
            $table->string('color', 6)->default('000000');
            $table->timestamps();
        });

        Schema::create('product_tags', function (Blueprint $table) {
            $table->uuid('product_id')->index();
            $table->uuid('tag_id')->index();

            $table->primary(['product_id', 'tag_id']);

            $table->foreign('product_id')->references('id')->on('products')->onDelete('cascade');
            $table->foreign('tag_id')->references('id')->on('tags')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_tags');
        Schema::dropIfExists('tags');
    }
}
