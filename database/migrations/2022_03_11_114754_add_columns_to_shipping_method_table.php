<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('shipping_methods', function (Blueprint $table) {
            $table->string('shipping_type')->default('none');
            $table->string('integration_key')->nullable();
            $table->string('app_id')->nullable()->default(null);
            $table->boolean('deletable')->default(false);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('shipping_methods', function (Blueprint $table) {
            $table->dropColumn('shipping_type');
            $table->dropColumn('integration_key');
            $table->dropColumn('app_id');
            $table->dropColumn('deletable');
        });
    }
};
