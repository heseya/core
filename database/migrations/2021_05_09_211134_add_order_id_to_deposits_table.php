<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddOrderIdToDepositsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('deposits', function (Blueprint $table) {
            $table->uuid('order_product_id')->after('item_id')->nullable();
        });

        Schema::table('statuses', function (Blueprint $table) {
            $table->boolean('cancel')->after('color')->default(false);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('deposits', function (Blueprint $table) {
            $table->dropColumn('order_product_id');
        });

        Schema::table('statuses', function (Blueprint $table) {
            $table->dropColumn('cancel');
        });
    }
}
