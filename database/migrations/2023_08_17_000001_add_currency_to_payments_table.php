<?php

use App\Models\Order;
use App\Models\Payment;
use Domain\Currency\Currency;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->string('currency', 3)->nullable();
        });

        Payment::query()->update([
            'currency' => Currency::DEFAULT->value,
            'amount' => DB::raw('amount * 100'),
        ]);

        Schema::table('payments', function (Blueprint $table) {
            $table->string('currency', 3)->nullable(false)->change();
            $table->decimal('amount', 27, 0)->change();
        });
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropColumn('currency');
        });
    }
};
