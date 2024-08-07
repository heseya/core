<?php

use Domain\PaymentMethods\Enums\PaymentMethodType;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('payment_methods', function (Blueprint $table) {
            $table->string('type')->default(PaymentMethodType::PREPAID->value);
            $table->boolean('creates_default_payment')->default(false);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payment_methods', function (Blueprint $table) {
            $table->removeColumn('type');
            $table->removeColumn('creates_default_payment');
        });
    }
};
