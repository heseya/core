<?php

use App\Models\Order;
use App\Services\Contracts\OrderServiceContract;
use App\Services\OrderService;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddSummaryToOrdersTable extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table): void {
            $table->float('summary', 19, 4)->default(0);
            $table->boolean('paid')->default(false);
        });

        /** @var OrderService $orderService */
        $orderService = app(OrderServiceContract::class);

        Order::chunk(100, fn ($order) => $order->each(
            fn (Order $order) => $order->update([
                'summary' => $orderService->calcSummary($order),
                'paid' => $order->paid_amount >= $orderService->calcSummary($order),
            ]),
        ));
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table): void {
            $table->dropColumn('summary');
            $table->dropColumn('paid');
        });
    }
}
