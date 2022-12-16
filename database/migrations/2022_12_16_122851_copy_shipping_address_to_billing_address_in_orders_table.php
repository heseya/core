<?php

use App\Models\Order;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Order::chunk(100, fn ($order) => $order->each(
            function (Order $order) {
                if ($order->billing_address_id === null) {
                    $order->update([
                        'billing_address_id' => $order->shipping_address_id,
                    ]);
                }
            }
        ));
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Order::chunk(100, fn ($order) => $order->each(
            function (Order $order) {
                if ($order->billing_address_id === $order->shipping_address_id) {
                    $order->update([
                        'billing_address_id' => null,
                    ]);
                }
            }
        ));
    }
};
