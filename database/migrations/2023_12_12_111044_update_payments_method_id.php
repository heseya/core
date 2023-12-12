<?php

use App\Models\PaymentMethod;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        $paymentsMethods = PaymentMethod::query()->whereNotNull('alias')->get()->unique('alias');

        foreach ($paymentsMethods as $paymentMethod) {
            DB::table('payments')->where('method', '=', $paymentMethod->alias)->update(['method_id' => $paymentMethod->getKey()]);
        }
    }

    public function down(): void
    {
        $paymentsMethods = PaymentMethod::query()->whereNotNull('alias')->get()->unique('alias');

        foreach ($paymentsMethods as $paymentMethod) {
            DB::table('payments')->where('method', '=', $paymentMethod->alias)->update(['method_id' => null]);
        }
    }
};
