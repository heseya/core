<?php

use App\Models\Order;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Order::query()
            ->whereNull('invoice_address_id')
            ->each(function ($order) {
                $order->invoice_address_id = $order->delivery_address_id;
                $order->save();
            });

        Schema::table('orders', function (Blueprint $table) {
            $table->renameColumn('invoice_address_id', 'billing_address_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->renameColumn('billing_address_id', 'invoice_address_id');
        });

        Order::query()
            ->whereColumn('invoice_address_id', 'delivery_address_id')
            ->each(function ($order) {
                $order->invoice_address_id = null;
                $order->save();
            });
    }
};
