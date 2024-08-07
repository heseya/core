<?php

use App\Models\Product;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddAvailableColumnToOptionsSchemasAndProducts extends Migration
{
    public function up(): void
    {
        Schema::table('options', function (Blueprint $table): void {
            $table->boolean('available')->default(false);
        });
        Schema::table('schemas', function (Blueprint $table): void {
            $table->boolean('available')->default(false);
        });
        Schema::table('products', function (Blueprint $table): void {
            $table->boolean('available')->default(false);
        });

        //        /** @var AvailabilityService $availabilityService */
        //        $availabilityService = app(AvailabilityServiceContract::class);
        //
        //        $items = Item::all();
        //        $items->each(fn ($item) => $availabilityService->calculateAvailabilityOnAllItemRelations($item));

        $products = Product::doesntHave('schemas')->get();
        $products->each(function (Product $product): void {
            Product::withoutSyncingToSearch(function () use ($product): void {
                $product->update(['available' => true]);
            });
        });
    }

    public function down(): void
    {
        Schema::table('options', function (Blueprint $table): void {
            $table->dropColumn('available');
        });
        Schema::table('schemas', function (Blueprint $table): void {
            $table->dropColumn('available');
        });
        Schema::table('products', function (Blueprint $table): void {
            $table->dropColumn('available');
        });
    }
}
