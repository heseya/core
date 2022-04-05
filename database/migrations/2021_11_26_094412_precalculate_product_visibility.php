<?php

use App\Models\Product;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class PrecalculateProductVisibility extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('products', function (Blueprint $table) {
           $table->boolean('public_legacy')->nullable(); // Deprecated, to be removed in 3.0
        });

        Product::chunk(100, fn (Collection $products) => $products->each(
            function (Product $product) {
                $isAnySetPublic = $product->sets->count() === 0 ||
                    $product->sets->where('public', true)->where('public_parent', true);

                $newPublic = $product->public && $isAnySetPublic;

                Product::withoutSyncingToSearch(function () use ($product, $newPublic): void {
                    $product->update([
                        'public_legacy' => $product->public,
                        'public' => $newPublic,
                    ]);
                });
            },
        ));
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Product::chunk(100, fn (Collection $products) => $products->each(
            fn (Product $product) => $product->update([
                'public' => $product->public_legacy,
            ]),
        ));

        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn('public_legacy');
        });
    }
}
