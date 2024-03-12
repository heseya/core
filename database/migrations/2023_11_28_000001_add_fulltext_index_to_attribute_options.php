<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('attribute_options', function (Blueprint $table): void {
            $table->fullText(
                ['name'],
                'options_fulltext',
            );
        });
    }

    public function down(): void
    {
        Schema::table('attribute_options', function (Blueprint $table): void {
            $table->dropFullText('options_fulltext');
        });
    }
};
