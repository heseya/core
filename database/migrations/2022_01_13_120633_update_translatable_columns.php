<?php

use App\Models\Option;
use App\Models\Product;
use App\Models\Schema;
use App\Models\Status;
use Domain\Language\Language;
use Domain\Page\Page;
use Domain\ProductSet\ProductSet;
use Domain\Seo\Models\SeoMetadata;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema as DbSchema;
use Support\Utils\Migrate;

class UpdateTranslatableColumns extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $lang = Language::query()->where('default', true)->value('id');

        DbSchema::table('products', function (Blueprint $table): void {
            $table->text('name')->change();
            $table->text('description_html')->nullable()->change();
            $table->text('description_short')->nullable()->change();
            $table->text('published')->nullable();
        });

        Product::query()->withTrashed()->update([
            'name' => Migrate::lang('name', $lang),
            'description_html' => Migrate::lang('description_html', $lang),
            'description_short' => Migrate::lang('description_short', $lang),
            'published' => [$lang],
        ]);

        DbSchema::table('schemas', function (Blueprint $table): void {
            $table->text('name')->change();
            $table->text('description')->nullable()->change();
            $table->text('published')->nullable();
        });

        Schema::query()->update([
            'name' => Migrate::lang('name', $lang),
            'description' => Migrate::lang('description', $lang),
            'published' => [$lang],
        ]);

        DbSchema::table('options', function (Blueprint $table): void {
            $table->text('name')->change();
        });

        Option::query()->update([
            'name' => Migrate::lang('name', $lang),
        ]);

        DbSchema::table('pages', function (Blueprint $table): void {
            $table->text('name')->change();
            $table->text('content_html')->nullable()->change();
            $table->text('published')->nullable();
        });

        Page::query()->withTrashed()->update([
            'name' => Migrate::lang('name', $lang),
            'content_html' => Migrate::lang('content_html', $lang),
            'published' => [$lang],
        ]);

        DbSchema::table('statuses', function (Blueprint $table): void {
            $table->text('name')->change();
            $table->text('description')->nullable()->change();
            $table->text('published')->nullable();
        });

        Status::query()->update([
            'name' => Migrate::lang('name', $lang),
            'description' => Migrate::lang('description', $lang),
            'published' => [$lang],
        ]);

        DbSchema::table('seo_metadata', function (Blueprint $table): void {
            $table->text('title')->nullable()->change();
            $table->text('description')->nullable()->change();
            $table->text('keywords')->nullable()->change();
            $table->text('no_index')->nullable()->change();
            $table->text('published')->nullable();
        });

        SeoMetadata::query()->withTrashed()->chunk(100, fn ($seo) => $seo->each(
            function (SeoMetadata $seo) use ($lang): void {
                $attr = $seo->getAttributes();
                $seo
                    ->setRawAttributes([
                        'no_index' => json_encode([$lang => (bool) $attr['no_index']]),
                    ])
                    ->setAttribute('published', [$lang])
                    ->setTranslation('title', $lang, $attr['title'])
                    ->setTranslation('description', $lang, $attr['description'])
                    ->replaceTranslations('keywords', [$lang => json_decode($attr['keywords'])])
                    ->save();
            },
        ));

        DbSchema::table('product_sets', function (Blueprint $table): void {
            $table->text('name')->change();
            $table->text('description_html')->nullable()->change();
            $table->text('published')->nullable();
        });

        ProductSet::query()->withTrashed()->update([
            'name' => Migrate::lang('name', $lang),
            'description_html' => Migrate::lang('description_html', $lang),
            'published' => [$lang],
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DbSchema::table('products', function (Blueprint $table): void {
            $table->dropColumn('published');
        });

        DbSchema::table('schemas', function (Blueprint $table): void {
            $table->dropColumn('published');
        });

        DbSchema::table('options', function (Blueprint $table): void {
            $table->dropColumn('published');
        });

        DbSchema::table('pages', function (Blueprint $table): void {
            $table->dropColumn('published');
        });

        DbSchema::table('statuses', function (Blueprint $table): void {
            $table->dropColumn('published');
        });

        DbSchema::table('seo_metadata', function (Blueprint $table): void {
            $table->dropColumn('published');
        });
    }
}
