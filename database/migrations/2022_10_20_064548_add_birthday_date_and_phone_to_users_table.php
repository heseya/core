<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->date('birthday_date')->nullable();
            $table->char('phone_country', 2)->nullable();
            $table->string('phone_number', 20)->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropColumn('birthday_date');
            $table->dropColumn('phone_country');
            $table->dropColumn('phone_number');
        });
    }
};
