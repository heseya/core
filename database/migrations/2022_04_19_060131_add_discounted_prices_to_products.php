<?php

use App\Models\Product;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table): void {
            $table->float('price_min_initial', 19, 4)->nullable();
            $table->float('price_max_initial', 19, 4)->nullable();
        });

        Product::chunk(100, fn (Collection $products) => $products->each(
            fn (Product $product) => $product->update([
                'price_min_initial' => $product->price_min,
                'price_max_initial' => $product->price_max,
            ]),
        ));

        Schema::create('product_sales', function (Blueprint $table): void {
            $table->uuid('product_id')->index();
            $table->uuid('sale_id')->index();

            $table->primary(['product_id', 'sale_id']);

            $table->foreign('product_id')->references('id')->on('products')->onDelete('cascade');
            $table->foreign('sale_id')->references('id')->on('discounts')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table): void {
            $table->dropColumn('price_min_initial');
            $table->dropColumn('price_max_initial');
        });
    }
};
