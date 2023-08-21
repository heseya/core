<?php

use Domain\Language\Language;
use Domain\ProductAttribute\Models\Attribute as AttributeAlias;
use Domain\ProductAttribute\Models\AttributeOption;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $language = Language::query()->where('default', true)->firstOrFail();
        $lang = $language->getKey();

        Schema::table('attributes', function (Blueprint $table) {
            $table->text('name')->change();
            $table->text('description')->change();
            $table->text('published')->nullable();
        });

        AttributeAlias::chunk(100, fn ($statuses) => $statuses->each(
            function (AttributeAlias $status) use ($lang): void {
                $attr = $status->getAttributes();
                $status
                    ->setAttribute('published', [$lang])
                    ->setTranslation('name', $lang, $attr['name'])
                    ->setTranslation('description', $lang, $attr['description'])
                    ->save();
            },
        ));


        Schema::table('attribute_options', function (Blueprint $table) {
            $table->text('name')->change();
            $table->text('published')->nullable();
        });

        AttributeOption::chunk(100, fn ($statuses) => $statuses->each(
            function (AttributeOption $status) use ($lang): void {
                $attr = $status->getAttributes();
                $status
                    ->setTranslation('name', $lang, $attr['name'])
                    ->save();
            },
        ));
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('attributes', function (Blueprint $table) {
            $table->string('name')->change();
            $table->dropColumn('published');
        });

        Schema::table('attribute_options', function (Blueprint $table) {
            $table->string('name')->change();
        });
    }
};
