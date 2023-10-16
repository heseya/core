<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('media', function (Blueprint $table): void {
            $table->string('url', 500)->change();
        });
    }

    public function down(): void
    {
        // dont shorten to prevent data loss
    }
};
