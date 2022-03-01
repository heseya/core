<?php

use App\Models\Item;
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
            $table->boolean('available')->nullable();
        });
        Schema::table('schemas', function (Blueprint $table) {
            $table->boolean('available')->nullable();
        });
        Schema::table('products', function (Blueprint $table) {
            $table->boolean('available')->nullable();
        });

        /** @var AvailabilityService $availabilityService */
        $availabilityService = app(AvailabilityServiceContract::class);

        $items = Item::all();

        $items->each(fn ($item) => $availabilityService->calculateAvailabilityOnOrderAndRestock($item));
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
