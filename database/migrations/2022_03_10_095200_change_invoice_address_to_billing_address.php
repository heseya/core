<?php

use App\Models\Order;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Order::query()
            ->whereNull('invoice_address_id')
            ->each(function ($order): void {
                $order->invoice_address_id = $order->delivery_address_id;
                $order->save();
            });

        Schema::table('orders', function (Blueprint $table): void {
            $table->renameColumn('invoice_address_id', 'billing_address_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table): void {
            $table->renameColumn('billing_address_id', 'invoice_address_id');
        });

        Order::query()
            ->whereColumn('invoice_address_id', 'delivery_address_id')
            ->each(function ($order): void {
                $order->invoice_address_id = null;
                $order->save();
            });
    }
};
