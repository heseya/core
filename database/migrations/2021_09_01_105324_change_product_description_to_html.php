<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use League\HTMLToMarkdown\HtmlConverter;

class ChangeProductDescriptionToHtml extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('products', function (Blueprint $table) {
            $table->text('description_html')->nullable();
        });

        DB::table('products')->orderBy('id')->chunk(100, function ($pages) {
            foreach ($pages as $page) {
                DB::table('products')->where('id', $page->id)->update([
                    'description_html' => $page->description_md,
                ]);
            }
        });

        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn('description_md');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('products', function (Blueprint $table) {
            $table->text('description_md');
        });

        $htmlConverter = new HtmlConverter(['strip_tags' => true]);

        DB::table('products')->orderBy('id')->chunk(100, function ($pages) use ($htmlConverter) {
            foreach ($pages as $page) {
                DB::table('products')->where('id', $page->id)->update([
                    'description_md' => $htmlConverter->convert($page->description_html),
                ]);
            }
        });

        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn('description_html');
        });
    }
}
