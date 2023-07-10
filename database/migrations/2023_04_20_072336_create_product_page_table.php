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
        Schema::create('product_page', function (Blueprint $table): void {
            $table->uuid('product_id')->index();
            $table->uuid('page_id')->index();

            $table->primary(['product_id', 'page_id']);

            $table->foreign('product_id')->references('id')
                ->on('products')->onDelete('cascade');
            $table->foreign('page_id')->references('id')
                ->on('pages')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_page');
    }
};
