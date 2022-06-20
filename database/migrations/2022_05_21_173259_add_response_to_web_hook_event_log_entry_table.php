<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('web_hook_event_log_entries', function (Blueprint $table): void {
            $table->json('payload')->nullable();
            $table->text('response')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('web_hook_event_log_entries', function (Blueprint $table): void {
            $table->dropColumn(['payload', 'response']);
        });
    }
};
