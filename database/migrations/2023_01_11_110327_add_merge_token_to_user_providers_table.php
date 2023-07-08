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
    public function up(): void
    {
        Schema::table('user_providers', function (Blueprint $table): void {
            $table->string('merge_token')->nullable();
            $table->dateTime('merge_token_expires_at')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::table('user_providers', function (Blueprint $table): void {
            $table->dropColumn('merge_token');
            $table->dropColumn('merge_token_expires_at');
        });
    }
};
