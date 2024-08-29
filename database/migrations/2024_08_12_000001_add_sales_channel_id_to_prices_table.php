<?php

declare(strict_types=1);

use App\Models\Price;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('prices', function (Blueprint $table): void {
            $table->uuid('sales_channel_id')->nullable();
            $table->uuid('price_map_id')->nullable();
        });

        Schema::table('prices', function (Blueprint $table): void {
            $table->dropUnique(['model_id', 'price_type', 'currency']);
        });

        Schema::table('prices', function (Blueprint $table): void {
            $table->unique(['model_id', 'price_type', 'currency', 'sales_channel_id']);
            $table->unique(['model_id', 'price_type', 'currency', 'price_map_id']);
        });
    }

    public function down(): void
    {
        Schema::table('prices', function (Blueprint $table): void {
            $table->dropUnique(['model_id', 'price_type', 'currency', 'sales_channel_id']);
            $table->dropUnique(['model_id', 'price_type', 'currency', 'price_map_id']);
        });

        Price::whereNotNull('sales_channel_id')->delete();
        Price::whereNotNull('price_map_id')->delete();

        Schema::table('prices', function (Blueprint $table): void {
            $table->dropColumn('sales_channel_id');
            $table->dropColumn('price_map_id');
        });

        Schema::table('prices', function (Blueprint $table): void {
            $table->unique(['model_id', 'price_type', 'currency']);
        });
    }
};
