<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddOrderIdToDepositsTable extends Migration
{
    public function up(): void
    {
        Schema::table('deposits', function (Blueprint $table): void {
            $table->uuid('order_product_id')->after('item_id')->nullable();
        });

        Schema::table('statuses', function (Blueprint $table): void {
            $table->boolean('cancel')->after('color')->default(false);
        });
    }

    public function down(): void
    {
        Schema::table('deposits', function (Blueprint $table): void {
            $table->dropColumn('order_product_id');
        });

        Schema::table('statuses', function (Blueprint $table): void {
            $table->dropColumn('cancel');
        });
    }
}
