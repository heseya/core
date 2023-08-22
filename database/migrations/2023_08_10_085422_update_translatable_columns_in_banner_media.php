<?php

use Domain\Banner\Models\BannerMedia;
use Domain\Language\Language;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Support\Utils\Migrate;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('banner_media', function (Blueprint $table) {
            $table->text('title')->change();
            $table->text('subtitle')->change();
            $table->text('published')->nullable();
        });

        $lang = Language::query()->where('default', true)->value('id');

        BannerMedia::query()->update([
            'title' => Migrate::lang('title', $lang),
            'subtitle' => Migrate::lang('subtitle', $lang),
            'published' => [$lang],
        ]);
    }

    public function down(): void
    {
        Schema::table('banner_media', function (Blueprint $table) {
            $table->string('title')->change();
            $table->string('subtitle')->change();
            $table->dropColumn('published');
        });
    }
};
