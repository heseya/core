<?php

use App\Models\Payment;
use Brick\Money\Money;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::table('payments')->lazyById()->each(function (object $payment) {
            $money = Money::of($payment->amount, 'PLN');

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

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->float('amount', 19, 4)->default(0);
        });

        DB::table('payments')->lazyById()->each(function (object $payment) {
            $price = DB::table('prices')
                ->where('model_id', $payment->id)
                ->first();

            $money = Money::of($price->value, 'PLN');

            DB::table('options')
                ->where('id', $payment->id)
                ->update(['amount' => $money->getAmount()]);
        });
    }
};
