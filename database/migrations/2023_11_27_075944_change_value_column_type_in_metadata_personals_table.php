<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('metadata_personals', function (Blueprint $table): void {
            $table->text('value')->change();
        });
    }

    public function down(): void
    {
        Schema::table('metadata_personals', function (Blueprint $table): void {
            $table->string('value')->change();
        });
    }
};
