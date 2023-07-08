<?php

use App\Models\ShippingMethod;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table): void {
            $table->uuid('digital_shipping_method_id')->nullable();
        });

        ShippingMethod::query()->where('shipping_type', 'none')->update([
            'shipping_type' => 'digital',
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table): void {
            $table->dropColumn('digital_shipping_method_id');
        });

        ShippingMethod::query()->where('shipping_type', 'digital')->update([
            'shipping_type' => 'none',
        ]);
    }
};
