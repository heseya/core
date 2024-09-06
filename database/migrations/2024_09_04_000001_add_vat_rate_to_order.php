<?php

declare(strict_types=1);

use App\Models\Order;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->string('vat_rate', 9)->default('0');
        });

        Order::query()->with('salesChannel')->chunkById(100, function (Collection $orders) {
            /** @var Order $order */
            foreach ($orders as $order) {
                $order->update(['vat_rate' => $order->salesChannel?->vat_rate ?? '0']);
            }
        });

        Schema::table('prices', function (Blueprint $table) {
            $table->boolean('is_net')->default(true)->change();
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn('vat_rate');
        });
    }
};
