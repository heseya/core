<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('price_map_product_prices', function (Blueprint $table): void {
            $table->boolean('is_net')->default(false)->change();
        });

        Schema::table('price_map_schema_option_prices', function (Blueprint $table): void {
            $table->boolean('is_net')->default(false)->change();
        });
    }

    public function down(): void {}
};
