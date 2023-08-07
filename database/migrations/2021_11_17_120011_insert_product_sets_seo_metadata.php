<?php

use App\Models\SeoMetadata;
use Domain\ProductSet\ProductSet;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class InsertProductSetsSeoMetadata extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('product_sets') && Schema::hasTable('seo_metadata')) {
            ProductSet::whereDoesntHave('seo')->chunk(100, fn ($set) => $set->each(
                fn (ProductSet $set) => SeoMetadata::create([
                    'global' => false,
                    'model_id' => $set->getKey(),
                    'model_type' => ProductSet::class,
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
