<?php

use App\Models\Language;
use App\Models\Option;
use App\Models\Page;
use App\Models\Product;
use App\Models\Schema;
use App\Models\SeoMetadata;
use App\Models\Status;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema as DbSchema;

class UpdateTranslatableColumns extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $language = Language::where('default', true)->firstOrFail();
        $lang = $language->getKey();

        DbSchema::table('products', function (Blueprint $table) {
            $table->text('name')->change();
            $table->text('description_html')->nullable()->change();
            $table->text('description_short')->nullable()->change();
            $table->text('published')->nullable();
        });

        Product::chunk(100, fn ($products) => $products->each(
            function (Product $product) use ($lang) {
                $attr = $product->getAttributes();
                $product
                    ->setAttribute('published', [$lang])
                    ->setTranslation('name', $lang, $attr['name'])
                    ->setTranslation('description_html', $lang, $attr['description_html'])
                    ->setTranslation('description_short', $lang, $attr['description_short'])
                    ->save();
            },
        ));

        DbSchema::table('schemas', function (Blueprint $table) {
            $table->text('name')->change();
            $table->text('description')->nullable()->change();
            $table->text('published')->nullable();
        });

        Schema::chunk(100, fn ($schemas) => $schemas->each(
            function (Schema $schema) use ($lang) {
                $attr = $schema->getAttributes();
                $schema
                    ->setAttribute('published', [$lang])
                    ->setTranslation('name', $lang, $attr['name'])
                    ->setTranslation('description', $lang, $attr['description'])
                    ->save();
            },
        ));

        DbSchema::table('options', function (Blueprint $table) {
            $table->text('name')->change();
            $table->text('published')->nullable();
        });

        Option::chunk(100, fn ($options) => $options->each(
            function (Option $option) use ($lang) {
                $attr = $option->getAttributes();
                $option
                    ->setAttribute('published', [$lang])
                    ->setTranslation('name', $lang, $attr['name'])
                    ->save();
            },
        ));

        DbSchema::table('pages', function (Blueprint $table) {
            $table->text('name')->change();
            $table->text('content_html')->nullable()->change();
            $table->text('published')->nullable();
        });

        Page::chunk(100, fn ($pages) => $pages->each(
            function (Page $page) use ($lang) {
                $attr = $page->getAttributes();
                $page
                    ->setAttribute('published', [$lang])
                    ->setTranslation('name', $lang, $attr['name'])
                    ->setTranslation('content_html', $lang, $attr['content_html'])
                    ->save();
            },
        ));

        DbSchema::table('statuses', function (Blueprint $table) {
            $table->text('name')->change();
            $table->text('description')->nullable()->change();
            $table->text('published')->nullable();
        });

        Status::chunk(100, fn ($statuses) => $statuses->each(
            function (Status $status) use ($lang) {
                $attr = $status->getAttributes();
                $status
                    ->setAttribute('published', [$lang])
                    ->setTranslation('name', $lang, $attr['name'])
                    ->setTranslation('description', $lang, $attr['description'])
                    ->save();
            },
        ));

        DbSchema::table('seo_metadata', function (Blueprint $table) {
            $table->text('title')->nullable()->change();
            $table->text('description')->nullable()->change();
            $table->text('keywords')->nullable()->change();
            $table->text('no_index')->change();
            $table->text('published')->nullable();
        });

        SeoMetadata::chunk(100, fn ($seo) => $seo->each(
            function (SeoMetadata $seo) use ($lang) {
                $attr = $seo->getAttributes();

                $seo
                    ->setRawAttributes([
                        'no_index' => json_encode([$lang => (boolean) $attr['no_index']]),
                    ])
                    ->setAttribute('published', [$lang])
                    ->setTranslation('title', $lang, $attr['title'])
                    ->setTranslation('description', $lang, $attr['description'])
                    ->replaceTranslations('keywords', [$lang => json_decode($attr['keywords'])])
                    ->save();
            },
        ));
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DbSchema::table('products', function (Blueprint $table) {
            $table->dropColumn('published');
        });

        DbSchema::table('schemas', function (Blueprint $table) {
            $table->dropColumn('published');
        });

        DbSchema::table('options', function (Blueprint $table) {
            $table->dropColumn('published');
        });

        DbSchema::table('pages', function (Blueprint $table) {
            $table->dropColumn('published');
        });

        DbSchema::table('statuses', function (Blueprint $table) {
            $table->dropColumn('published');
        });

        DbSchema::table('seo_metadata', function (Blueprint $table) {
            $table->dropColumn('published');
        });
    }
}
