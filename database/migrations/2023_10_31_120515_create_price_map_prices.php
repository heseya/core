<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('price_map_prices', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('price_map_id');
            $table->uuid('model_id');
            $table->string('model_type');
            $table->decimal('value', 27, 0);
            $table->string('currency');
            $table->boolean('is_net');

            $table->unique(['model_id', 'price_map_id']);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('price_map_prices');
    }
};
