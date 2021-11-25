<?php

use App\Models\ProductSet;
use App\Models\SeoMetadata;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

class InsertProductSetsSeoMetadata extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
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

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        if (Schema::hasTable('seo_metadata')) {
            DB::table('seo_metadata')->delete();
        }
    }
}
