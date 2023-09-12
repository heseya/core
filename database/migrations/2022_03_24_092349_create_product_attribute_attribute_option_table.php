<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateProductAttributeAttributeOptionTable extends Migration
{
    public function up(): void
    {
        Schema::table('product_attribute', function (Blueprint $table): void {
            $table->dropForeign('product_attribute_option_id_foreign');

            $table->dropColumn('option_id');
            $table->dropPrimary(['product_id', 'attribute_id']);
            $table->unique(['product_id', 'attribute_id']);
            $table->uuid('id')->primary();
        });

        Schema::create('product_attribute_attribute_option', function (Blueprint $table): void {
            $table->uuid('product_attribute_id');
            $table->uuid('attribute_option_id');

            $table->foreign('product_attribute_id')->references('id')->on('product_attribute')->onDelete('cascade');
            $table->foreign('attribute_option_id')->references('id')->on('attribute_options')->onDelete('cascade');

            $table->primary(['product_attribute_id', 'attribute_option_id'], 'product_attribute_attribute_option_primary');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_attribute_attribute_option');

        Schema::table('product_attribute', function (Blueprint $table): void {
            $table->dropPrimary('product_attribute_id_primary');
            $table->dropColumn('id');

            $table->dropUnique(['product_id', 'attribute_id']);
            $table->primary(['product_id', 'attribute_id']);

            $table->uuid('option_id');
//            $table->foreign('option_id')->references('id')->on('attribute_options')->onDelete('cascade');
        });
    }
}
