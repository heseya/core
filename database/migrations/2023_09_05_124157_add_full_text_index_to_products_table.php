<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table): void {
            $table->text('search_values')->nullable();
            $table->fullText(
                ['search_values', 'slug', 'name', 'description_short', 'description_html'],
                'products_fulltext',
            );
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table): void {
            $table->dropFullText('products_fulltext');
            $table->dropColumn('search_values');
        });
    }
};
