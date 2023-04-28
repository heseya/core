<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::table('deposits')
            ->whereNull('shipping_date')
            ->whereNull('shipping_time')
            ->update(['shipping_time' => 0]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Do nothing
    }
};
