<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ChangePageContentToHtml extends Migration
{
    public function up(): void
    {
        Schema::table('pages', function (Blueprint $table): void {
            $table->text('content_html');
        });

        DB::table('pages')->orderBy('id')->chunk(100, function ($pages): void {
            foreach ($pages as $page) {
                DB::table('pages')->where('id', $page->id)->update([
                    'content_html' => $page->content_md,
                ]);
            }
        });

        Schema::table('pages', function (Blueprint $table): void {
            $table->dropColumn('content_md');
        });
    }

    public function down(): void
    {
        Schema::table('pages', function (Blueprint $table): void {
            $table->text('content_md');
        });

        DB::table('pages')->orderBy('id')->chunk(100, function ($pages): void {
            foreach ($pages as $page) {
                DB::table('pages')->where('id', $page->id)->update([
                    'content_md' => $page->content_html,
                ]);
            }
        });

        Schema::table('pages', function (Blueprint $table): void {
            $table->dropColumn('content_html');
        });
    }
}
