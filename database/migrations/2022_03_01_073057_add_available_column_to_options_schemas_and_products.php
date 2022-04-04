<?php

use App\Models\Item;
use App\Models\Product;
use App\Services\AvailabilityService;
use App\Services\Contracts\AvailabilityServiceContract;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddAvailableColumnToOptionsSchemasAndProducts extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('options', function (Blueprint $table) {
            $table->boolean('available')->default(false);
        });
        Schema::table('schemas', function (Blueprint $table) {
            $table->boolean('available')->default(false);
        });
        Schema::table('products', function (Blueprint $table) {
            $table->boolean('available')->default(false);
        });

        /** @var AvailabilityService $availabilityService */
        $availabilityService = app(AvailabilityServiceContract::class);

        $items = Item::all();
        $items->each(fn ($item) => $availabilityService->calculateAvailabilityOnOrderAndRestock($item));

        $products = Product::doesntHave('schemas')->get();
        $products->each(function (Product $product): void {
            Product::withoutSyncingToSearch(function () use ($product): void {
                $product->update(['available' => true]);
            });
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('options', function (Blueprint $table) {
            $table->dropColumn('available');
        });
        Schema::table('schemas', function (Blueprint $table) {
            $table->dropColumn('available');
        });
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn('available');
        });
    }
}
