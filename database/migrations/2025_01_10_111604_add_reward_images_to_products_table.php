<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_reward_images', function (Blueprint $table): void {
            $table->uuid('media_id')->index();
            $table->uuid('product_id')->index();
            $table->unsignedTinyInteger('order')->default(0);

            $table->primary(['media_id', 'product_id']);

            $table->foreign('media_id')->references('id')->on('media')->onDelete('cascade');
            $table->foreign('product_id')->references('id')->on('products')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_reward_images');
    }
};
