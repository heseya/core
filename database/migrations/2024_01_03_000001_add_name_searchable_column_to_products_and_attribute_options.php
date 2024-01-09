<?php

use App\Models\Product;
use Domain\ProductAttribute\Models\AttributeOption;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->string('searchable_name', 2048)->nullable();
        });
        Schema::table('attribute_options', function (Blueprint $table) {
            $table->string('searchable_name', 2048)->nullable();
        });
        Product::query()->chunkById(100, function (Product $product) {
            $product->touch();
            $product->save();
        });
        AttributeOption::query()->chunkById(100, function (AttributeOption $option) {
            $option->touch();
            $option->save();
        });
    }

    public function down(): void
    {
        Schema::table('attribute_options', function (Blueprint $table) {
            $table->dropColumn('searchable_name');
        });
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn('searchable_name');
        });
    }
};
