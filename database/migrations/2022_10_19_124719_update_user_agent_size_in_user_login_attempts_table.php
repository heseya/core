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
        Schema::table('user_login_attempts', function (Blueprint $table): void {
            $table->string('user_agent', 1024)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('user_login_attempts', function (Blueprint $table): void {
            $table->string('user_agent', 255)->change();
        });
    }
};
