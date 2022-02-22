<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddUserTypeToOrdersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->string('user_type')->nullable();
            $table->uuid('user_id')->nullable();

            $table->index(['user_id', 'user_type'], 'orders_user_id_user_type_index');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn('user_type');
            $table->dropColumn('user_id');
            $table->dropIndex('orders_user_id_user_type_index');
        });
    }
}
