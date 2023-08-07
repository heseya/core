<?php

use App\Models\SeoMetadata;
use Domain\Page\Page;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class InsertPagesSeoMetadata extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('pages') && Schema::hasTable('seo_metadata')) {
            Page::whereDoesntHave('seo')->chunk(100, fn ($page) => $page->each(
                fn (Page $page) => SeoMetadata::create([
                    'global' => false,
                    'model_id' => $page->getKey(),
                    'model_type' => Page::class,
                ])
            ));
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('seo_metadata')) {
            DB::table('seo_metadata')->delete();
        }
    }
}
