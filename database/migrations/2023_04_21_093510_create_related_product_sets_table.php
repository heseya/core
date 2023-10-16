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
        Schema::create('related_product_sets', function (Blueprint $table): void {
            $table->uuid('product_id')->index();
            $table->uuid('product_set_id')->index();

            $table->primary(['product_id', 'product_set_id']);

            $table->foreign('product_id')->references('id')
                ->on('products')->onDelete('cascade');
            $table->foreign('product_set_id')->references('id')
                ->on('product_sets')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('related_product_sets');
    }
};
