<?php

use App\Models\Product;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('products', function (Blueprint $table) {
            $table->float('min_price_discounted', 19, 4)->nullable();
            $table->float('max_price_discounted', 19, 4)->nullable();
        });

        Product::chunk(100, fn (Collection $products) => $products->each(
            fn (Product $product) => $product->update([
                'min_price_discounted' => $product->price_min,
                'max_price_discounted' => $product->price_max,
            ]),
        ));

        Schema::create('product_sales', function (Blueprint $table) {
            $table->uuid('product_id')->index();
            $table->uuid('sale_id')->index();

            $table->primary(['product_id', 'sale_id']);

            $table->foreign('product_id')->references('id')->on('products')->onDelete('cascade');
            $table->foreign('sale_id')->references('id')->on('discounts')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn('min_price_discounted');
            $table->dropColumn('max_price_discounted');
        });
    }
};
