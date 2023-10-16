<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddUserTypeToOrdersTable extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table): void {
            $table->string('user_type')->nullable();
            $table->uuid('user_id')->nullable();

            $table->index(['user_id', 'user_type'], 'orders_user_id_user_type_index');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table): void {
            $table->dropIndex('orders_user_id_user_type_index');
            $table->dropColumn('user_type');
            $table->dropColumn('user_id');
        });
    }
}
