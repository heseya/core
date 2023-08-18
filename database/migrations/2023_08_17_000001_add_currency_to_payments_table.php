<?php

use App\Models\Order;
use App\Models\Payment;
use Domain\Currency\Currency;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->string('currency', 3)->nullable(true);
        });

        Order::query()->lazyById()->each(function (Order $order) {
            $order->payments()->update([
                'currency' => $order->currency->value
            ]);
        });

        Payment::query()->whereNull('currency')->update([
            'currency' => Currency::DEFAULT->value
        ]);

        Schema::table('payments', function (Blueprint $table) {
            $table->string('currency', 3)->nullable(false)->change();
        });
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropColumn('currency');
        });
    }
};
