<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class PriceRanges extends Migration
{
    public function up(): void
    {
        Schema::create('price_ranges', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->float('start', 19, 4);
            $table->uuid('shipping_method_id')->index()->nullable();
            $table->timestamps();

            $table->unique(['start', 'shipping_method_id']);

            $table->foreign('shipping_method_id')->references('id')->on('shipping_methods')->onDelete('cascade');
        });

        Schema::create('prices', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('model_id')->index();
            $table->string('model_type')->index();
            $table->float('value', 19, 4);
            $table->timestamps();

            $table->unique(['value', 'model_type', 'model_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('price_ranges');
        Schema::dropIfExists('prices');
    }
}
