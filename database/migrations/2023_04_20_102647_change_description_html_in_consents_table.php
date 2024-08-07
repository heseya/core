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
        Schema::table('consents', function (Blueprint $table): void {
            $table->text('description_html')->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void {}
};
