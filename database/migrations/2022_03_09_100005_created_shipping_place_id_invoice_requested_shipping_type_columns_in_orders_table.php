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
        Schema::table('orders', function (Blueprint $table) {
            $table->string('shipping_place')->nullable();
            $table->boolean('invoice_requested')->nullable()->default(0);
            $table->string('shipping_type')->default('address');
        });

        Order::query()
            ->whereNotNull('invoice_address_id')
            ->each(
                fn ($order) => $order->update(['invoice_requested' => true]),
            );
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn('shipping_place');
            $table->dropColumn('invoice_requested');
            $table->dropColumn('shipping_type');
        });
    }
};
