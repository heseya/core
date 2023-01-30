<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateItemProductTable extends Migration
{
    public function up(): void
    {
        Schema::create('item_product', function (Blueprint $table): void {
            $table->foreignUuid('item_id')->index()->references('id')->on('items')->onDelete('cascade');
            $table->foreignUuid('product_id')->index()->references('id')->on('products')->onDelete('cascade');
            $table->decimal('quantity', 16, 4)->default(0);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('item_product');
    }
}
