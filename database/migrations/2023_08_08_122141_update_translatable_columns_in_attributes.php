<?php

use Domain\Language\Language;
use Domain\ProductAttribute\Models\Attribute as AttributeAlias;
use Domain\ProductAttribute\Models\AttributeOption;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Support\Utils\Migrate;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $lang = Language::query()->where('default', true)->value('id');

        Schema::table('attributes', function (Blueprint $table) {
            $table->text('name')->change();
            $table->text('description')->change();
            $table->text('published')->nullable();
        });

        AttributeAlias::query()->update([
            'name' => Migrate::lang('name', $lang),
            'description' => Migrate::lang('description', $lang),
            'published' => [$lang],
        ]);

        Schema::table('attribute_options', function (Blueprint $table) {
            $table->text('name')->change();
        });

        AttributeOption::query()->update([
            'name' => Migrate::lang('name', $lang),
        ]);
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
