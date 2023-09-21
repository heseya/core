<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('sales_channels_shipping_methods', function (Blueprint $table): void {
            $table->uuid('sales_channel_id')->index();
            $table->uuid('shipping_method_id')->index();

            $table->primary(['sales_channel_id', 'shipping_method_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('shipping_methods', function (Blueprint $table): void {
            $table->dropColumn('vat_rate');
        });
    }
};
