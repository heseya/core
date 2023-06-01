<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('order_discounts', function (Blueprint $table) {
            $table->uuid('id')->nullable();
        });

        DB::table('order_discounts')->lazyById()->each(function (object $discount) {
            DB::table('order_discounts')
                ->where('discount_id', $discount->discount_id)
                ->where('model_id', $discount->model_id)
                ->where('model_type', $discount->model_type)
                ->update(['id' => Str::uuid()]);
        });

        Schema::table('order_discounts', function (Blueprint $table) {
            $table->dropPrimary();
            $table->uuid('id')->primary()->change();
        });
    }

    public function down(): void
    {
        Schema::table('order_discounts', function (Blueprint $table) {
            $table->dropPrimary();
            $table->primary(['discount_id', 'model_id', 'model_type']);
            $table->dropColumn('id');
        });
    }
};
