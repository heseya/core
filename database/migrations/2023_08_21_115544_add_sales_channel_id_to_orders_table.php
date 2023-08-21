<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->uuid('sales_channel_id')->nullable();

            $table->foreign('sales_channel_id')->references('id')->on('sales_channels')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropForeign('sales_channels_foreign');
            $table->dropColumn('sales_channel_id');
        });
    }
};
