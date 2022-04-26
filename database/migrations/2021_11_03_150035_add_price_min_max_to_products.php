<?php

use App\Models\Product;
use App\Services\Contracts\ProductServiceContract;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Schema;

class AddPriceMinMaxToProducts extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table): void {
            $table->float('price_min', 19, 4)->nullable();
            $table->float('price_max', 19, 4)->nullable();
        });

        /** @var ProductServiceContract $productService */
        $productService = App::make(ProductServiceContract::class);

        Product::chunk(100, fn ($products) => $products->each(
            fn (Product $product) => $productService->updateMinMaxPrices($product),
        ));
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table): void {
            $table->dropColumn('price_min');
            $table->dropColumn('price_max');
        });
    }
}
