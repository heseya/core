<?php

declare(strict_types=1);

use App\Models\Payment;
use Brick\Math\RoundingMode;
use Brick\Money\Money;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration {
    public function up(): void
    {
        DB::table('payments')->lazyById()->each(function (object $payment) {
            $money = Money::of($payment->amount, 'PLN', roundingMode: RoundingMode::HALF_UP);

            DB::table('prices')->insert([
                'id' => Str::uuid(),
                'model_id' => $payment->id,
                'model_type' => Payment::class,
                'value' => $money->getMinorAmount(),
            ]);
        });

        Schema::table('payments', function (Blueprint $table) {
            $table->dropColumn('amount');
        });
    }

    public function down(): void
    {
        $amountColumn = fn (Blueprint $table) => $table->float('amount', 19, 4);

        Schema::table('payments', function (Blueprint $table) use ($amountColumn) {
            $amountColumn($table)->nullable();
        });

        DB::table('payments')->lazyById()->each(function (object $payment) {
            $price = DB::table('prices')
                ->where('model_id', $payment->id)
                ->first();

            $money = Money::of($price->value, 'PLN');

            DB::table('payments')
                ->where('id', $payment->id)
                ->update(['amount' => $money->getAmount()]);
        });

        Schema::table('payments', function (Blueprint $table) use ($amountColumn) {
            $amountColumn($table)->nullable(false)->change();
        });
    }
};
