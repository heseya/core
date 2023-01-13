<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration {
    public function up(): void
    {
        Schema::rename('responsive_media', 'banner_media');

        Schema::table('banner_media', function (Blueprint $table): void {
            $table->string('url')->nullable();
            $table->string('title')->nullable();
            $table->string('subtitle')->nullable();
        });

        DB::statement('UPDATE banner_media, banners
SET banner_media.url = banners.url, banner_media.title = banners.name
where banner_media.banner_id = banners.id');

        Schema::table('banners', function (Blueprint $table): void {
            $table->dropColumn('url');
        });

        Schema::table('media_responsive_media', function (Blueprint $table): void {
            $table->renameColumn('responsive_media_id', 'banner_media_id');
        });
    }

    public function down(): void
    {
        Schema::rename('banner_media', 'responsive_media');

        Schema::table('banners', function (Blueprint $table): void {
            $table->string('url')->nullable();
        });

        DB::statement('UPDATE responsive_media, banners
SET banners.url = responsive_media.url where banners.id = responsive_media.banner_id');

        Schema::table('responsive_media', function (Blueprint $table): void {
            $table->dropColumn('title');
            $table->dropColumn('subtitle');
            $table->dropColumn('url');
        });

        Schema::table('media_responsive_media', function (Blueprint $table): void {
            $table->renameColumn('banner_media_id', 'responsive_media_id');
        });
    }
};
