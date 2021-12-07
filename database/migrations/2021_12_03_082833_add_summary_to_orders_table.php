<?php

use App\Models\Order;
use App\Services\Contracts\OrderServiceContract;
use App\Services\OrderService;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddSummaryToOrdersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->float('summary', 19, 4)->default(0);
            $table->boolean('paid')->default(false);
        });

        /** @var OrderService $orderService */
        $orderService = app(OrderServiceContract::class);

        Order::chunk(100, fn ($order) => $order->each(
            fn (Order $order) => $order->update([
                'summary' => $orderService->calcSummary($order),
                'paid' => $order->paid_amount >= $orderService->calcSummary($order),
            ])
        ));
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn('summary');
            $table->dropColumn('paid');
        });
    }
}
