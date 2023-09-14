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
        Schema::table('redirects', function (Blueprint $table): void {
            $table->boolean('enabled')->default(true);
            $table->renameColumn('slug', 'source_url');
            $table->renameColumn('url', 'target_url');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('redirects', function (Blueprint $table): void {
            $table->dropColumn('enabled');
            $table->renameColumn('source_url', 'slug');
            $table->renameColumn('target_url', 'url');
        });
    }
};
