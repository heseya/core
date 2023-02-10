<?php

use App\Enums\PaymentStatus;
use App\Models\Payment;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Payment::query()
            ->where('paid', false)
            ->update(['status' => PaymentStatus::PENDING]);

        Payment::query()
            ->where('paid', true)
            ->update(['status' => PaymentStatus::SUCCESSFUL]);

        Schema::table('payments', function (Blueprint $table) {
            $table->dropColumn('paid');
            $table->string('status')->nullable(false)->change();
        });

        Schema::table('payment_methods', function (Blueprint $table): void {
            $table->string('alias')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->boolean('paid')->default(false);
        });

        Payment::query()
            ->where('status', PaymentStatus::PENDING)
            ->update(['paid' => false]);

        Payment::query()
            ->where('status', PaymentStatus::FAILED)
            ->update(['paid' => false]);

        Payment::query()
            ->where('status', PaymentStatus::SUCCESSFUL)
            ->update(['paid' => true]);
    }
};
