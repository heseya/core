<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('price_map_product_prices', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('price_map_id');
            $table->uuid('product_id');

            $table->decimal('value', 27, 0);
            $table->string('currency');
            $table->boolean('is_net');

            $table->timestamps();

            $table->unique(['product_id', 'price_map_id']);
            $table->foreign('product_id')->references('id')->on('products')->cascadeOnDelete();
        });

        Schema::create('price_map_schema_option_prices', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('price_map_id');
            $table->uuid('option_id');

            $table->decimal('value', 27, 0);
            $table->string('currency');
            $table->boolean('is_net');

            $table->timestamps();

            $table->unique(['option_id', 'price_map_id']);
            $table->foreign('option_id')->references('id')->on('options')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('price_map_product_prices');
        Schema::dropIfExists('price_map_schema_option_prices');
    }
};
