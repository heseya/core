<?php

use App\Models\Product;
use App\Models\SeoMetadata;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Str;

class InsertProductsSeoMetadata extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (Schema::hasTable('products') && Schema::hasTable('seo_metadata')) {
            Product::whereDoesntHave('seo')->chunk(100, fn ($products) => $products->each(
                fn (Product $product) => SeoMetadata::create([
                    'global' => false,
                    'model_id' => $product->id,
                    'model_type' => Product::class,
                ]))
            );
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