<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddTfaToUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('tfa_type')->nullable();
            $table->string('tfa_secret')->nullable();
            $table->boolean('is_tfa_active')->default(false);
        });

        Schema::create('one_time_security_codes', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('code');

            $table->uuid('user_id');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');

            $table->dateTime('expires_at')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('tfa_type');
            $table->dropColumn('tfa_secret');
            $table->dropColumn('is_tfa_active');
        });

        Schema::dropIfExists('one_time_security_codes');
    }
}
