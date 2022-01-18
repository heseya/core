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
            $table->json('name')->change();
            $table->json('description_html')->nullable()->change();
            $table->json('description_short')->nullable()->change();
        });

        Product::chunk(100, fn ($products) => $products->each(
            function (Product $product) use ($lang) {
                $attr = $product->getAttributes();
                $product
                    ->setTranslation('name', $lang, $attr['name'])
                    ->setTranslation('description_html', $lang, $attr['description_html'])
                    ->setTranslation('description_short', $lang, $attr['description_short'])
                    ->save();
            },
        ));

        DbSchema::table('schemas', function (Blueprint $table) {
            $table->json('name')->change();
            $table->json('description')->nullable()->change();
        });

        Schema::chunk(100, fn ($schemas) => $schemas->each(
            function (Schema $schema) use ($lang) {
                $attr = $schema->getAttributes();
                $schema
                    ->setTranslation('name', $lang, $attr['name'])
                    ->setTranslation('description', $lang, $attr['description'])
                    ->save();
            },
        ));

        DbSchema::table('options', function (Blueprint $table) {
            $table->json('name')->change();
        });

        Option::chunk(100, fn ($options) => $options->each(
            function (Option $option) use ($lang) {
                $attr = $option->getAttributes();
                $option
                    ->setTranslation('name', $lang, $attr['name'])
                    ->save();
            },
        ));

        DbSchema::table('pages', function (Blueprint $table) {
            $table->json('name')->change();
            $table->json('content_html')->nullable()->change();
        });

        Page::chunk(100, fn ($pages) => $pages->each(
            function (Page $page) use ($lang) {
                $attr = $page->getAttributes();
                $page
                    ->setTranslation('name', $lang, $attr['name'])
                    ->setTranslation('content_html', $lang, $attr['content_html'])
                    ->save();
            },
        ));

        DbSchema::table('statuses', function (Blueprint $table) {
            $table->json('name')->change();
            $table->json('description')->nullable()->change();
        });

        Status::chunk(100, fn ($statuses) => $statuses->each(
            function (Status $status) use ($lang) {
                $attr = $status->getAttributes();
                $status
                    ->setTranslation('name', $lang, $attr['name'])
                    ->setTranslation('description', $lang, $attr['description'])
                    ->save();
            },
        ));

        DbSchema::table('seo_metadata', function (Blueprint $table) {
            $table->json('title')->nullable()->change();
            $table->json('description')->nullable()->change();
            $table->json('keywords')->nullable()->change();
//            $table->json('og_image')->nullable()->change();
            $table->json('no_index')->change();
        });

        SeoMetadata::chunk(100, fn ($seo) => $seo->each(
            function (SeoMetadata $seo) use ($lang) {
                $attr = $seo->getAttributes();

                $seo
                    ->setRawAttributes(['no_index' => json_encode([$lang => (boolean) $attr['no_index']])])
                    ->setTranslation('title', $lang, $attr['title'])
                    ->setTranslation('description', $lang, $attr['description'])
                    ->replaceTranslations('keywords', [$lang => json_decode($attr['keywords'])])
//                    ->setTranslation('og_image', $lang, $attr['og_image'])
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
        //
    }
}
