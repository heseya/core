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
        Schema::table('shipping_methods', function (Blueprint $table): void {
            $table->boolean('payment_on_delivery')->default(false);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('shipping_methods', function (Blueprint $table): void {
            $table->dropColumn('payment_on_delivery');
        });
    }
};
