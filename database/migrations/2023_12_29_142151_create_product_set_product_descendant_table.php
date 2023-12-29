<?php

use Domain\ProductSet\ProductSetService;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_set_product_descendant', function (Blueprint $table) {
            $table->uuid('product_id');
            $table->uuid('product_set_id');
            $table->unsignedInteger('order')->nullable();

            $table->primary(['product_id', 'product_set_id']);

            $table->foreign('product_id')->references('id')
                ->on('products')->onDelete('cascade');
            $table->foreign('product_set_id')->references('id')
                ->on('product_sets')->onDelete('cascade');
        });

        /** @var ProductSetService $service */
        $service = app(ProductSetService::class);

        $service->attachAllProductsToAncestorSets();
    }

    public function down(): void
    {
        Schema::drop('product_set_product_descendant');
    }
};
