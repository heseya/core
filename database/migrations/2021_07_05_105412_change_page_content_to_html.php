<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use League\HTMLToMarkdown\HtmlConverter;

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

        $htmlConverter = new HtmlConverter(['strip_tags' => true]);

        DB::table('pages')->orderBy('id')->chunk(100, function ($pages) use ($htmlConverter): void {
            foreach ($pages as $page) {
                DB::table('pages')->where('id', $page->id)->update([
                    'content_md' => $htmlConverter->convert($page->content_html),
                ]);
            }
        });

        Schema::table('pages', function (Blueprint $table): void {
            $table->dropColumn('content_html');
        });
    }
}
