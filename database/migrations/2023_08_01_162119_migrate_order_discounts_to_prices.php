<?php

declare(strict_types=1);

use App\Models\Discount;
use Brick\Math\BigDecimal;
use Brick\Math\RoundingMode;
use Brick\Money\Money;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Ramsey\Uuid\Uuid;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('order_discounts', function (Blueprint $table) {
            $table->float('value', 19, 4)->nullable()->change();
            $table->float('amount', 27, 0)->nullable();
            $table->string('currency', 3)->nullable();
        });

        DB::table('order_discounts')->orderBy('discount_id')->lazy()->each(function (object $orderDiscount) {
            if ($orderDiscount->type === 'percentage') {
                return;
            }

            if ($orderDiscount->type !== 'amount') {
                throw new Exception('Unknown discount type: ' . $orderDiscount->type);
            }

            $amount = Money::of($orderDiscount->value, 'PLN', roundingMode: RoundingMode::HALF_UP);

            DB::table('order_discounts')
                ->where('discount_id', $orderDiscount->discount_id)
                ->where('model_id', $orderDiscount->model_id)
                ->update([
                    'value' => null,
                    'amount' => $amount->getMinorAmount(),
                    'currency' => $amount->getCurrency()->getCurrencyCode(),
                ]);
        });

        Schema::table('order_discounts', function (Blueprint $table) {
            $table->dropColumn('type');
            $table->renameColumn('value', 'percentage');
        });

        Schema::table('order_discounts', function (Blueprint $table) {
            $table->decimal('percentage', 7, 4)->nullable()->change();
        });
    }

    public function down(): void
    {
        $valueColumn = fn (Blueprint $table) => $table->float('value', 19, 4);

        Schema::table('order_discounts', function (Blueprint $table) {
            $table->string('type', 255);
            $table->renameColumn('percentage', 'value');
        });

        Schema::table('order_discounts', function (Blueprint $table) use ($valueColumn) {
            $valueColumn($table)->nullable()->change();
        });

        DB::table('order_discounts')->orderBy('discount_id')->lazy()->each(function (object $orderDiscount) {
            DB::table('order_discounts')
                ->where('discount_id', $orderDiscount->discount_id)
                ->where('model_id', $orderDiscount->model_id)
                ->update([
                    'type' => $orderDiscount->amount ? 'amount' : 'percentage',
                    'value' => $orderDiscount->amount ?? $orderDiscount->value,
                ]);
        });

        Schema::table('order_discounts', function (Blueprint $table) use ($valueColumn) {
            $valueColumn($table)->nullable(false)->change();
            $table->dropColumn(['amount', 'currency']);
        });
    }
};
