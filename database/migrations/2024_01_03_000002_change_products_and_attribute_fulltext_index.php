<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropFullText('products_name_fulltext');
            $table->dropFullText('products_fulltext');
        });
        Schema::table('attribute_options', function (Blueprint $table): void {
            $table->dropFullText('options_fulltext');
        });

        Schema::table('products', function (Blueprint $table) {
            $table->fullText(
                ['searchable_name'],
                'products_name_fulltext',
            );
            $table->fullText(
                ['search_values', 'searchable_name', 'description_short', 'description_html'],
                'products_fulltext',
            );
        });
        Schema::table('attribute_options', function (Blueprint $table) {
            $table->fullText(
                ['searchable_name'],
                'options_fulltext',
            );
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropFullText('products_name_fulltext');
            $table->dropFullText('products_fulltext');
        });
        Schema::table('attribute_options', function (Blueprint $table): void {
            $table->dropFullText('options_fulltext');
        });

        Schema::table('products', function (Blueprint $table) {
            $table->fullText(
                ['name'],
                'products_name_fulltext',
            );
            $table->fullText(
                ['search_values', 'name', 'description_short', 'description_html'],
                'products_fulltext',
            );
        });
        Schema::table('attribute_options', function (Blueprint $table) {
            $table->fullText(
                ['name'],
                'options_fulltext',
            );
        });
    }
};
