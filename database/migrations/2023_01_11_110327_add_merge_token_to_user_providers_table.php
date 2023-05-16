<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('user_providers', function (Blueprint $table) {
            $table->string('merge_token')->nullable();
            $table->dateTime('merge_token_expires_at')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('user_providers', function (Blueprint $table) {
            $table->dropColumn('merge_token');
            $table->dropColumn('merge_token_expires_at');
        });
    }
};
