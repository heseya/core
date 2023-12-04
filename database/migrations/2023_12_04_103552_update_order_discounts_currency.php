<?php

use Brick\Math\RoundingMode;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Money\Money;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::table('order_discounts')->where('percentage', '!=', null)->orderBy('discount_id')->lazy()->each(function (object $orderDiscount) {
            $currency = $this->getModelClass($orderDiscount->model_type)::query()->where('id', '=', $orderDiscount->model_id)->first()->currency;

            DB::table('order_discounts')
                ->where('discount_id', $orderDiscount->discount_id)
                ->where('model_id', $orderDiscount->model_id)
                ->update([
                    'currency' => $currency,
                ]);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('order_discounts')->where('percentage', '!=', null)->orderBy('discount_id')->lazy()->each(function (object $orderDiscount) {
            DB::table('order_discounts')
                ->where('discount_id', $orderDiscount->discount_id)
                ->where('model_id', $orderDiscount->model_id)
                ->update([
                    'currency' => null,
                ]);
        });
    }

    private function getModelClass(string $model): string
    {
        return match ($model) {
            'Order' => \App\Models\Order::class,
            'OrderProduct' => \App\Models\OrderProduct::class,
        };
    }
};
