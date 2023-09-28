<?php

use App\Models\Price;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table((new Price())->getTable(), function (Blueprint $table) {
            $table->uuid('sales_channel_id')->nullable();
            $table->dropUnique(['model_id', 'price_type', 'currency']);
            $table->unique(['model_id', 'price_type', 'currency', 'sales_channel_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table((new Price())->getTable(), function (Blueprint $table) {
            $table->dropUnique(['model_id', 'price_type', 'currency', 'sales_channel_id']);
            $table->dropColumn('sales_channel_id');
            $table->unique(['model_id', 'price_type', 'currency']);
        });
    }
};
