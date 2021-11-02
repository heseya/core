<?php

use App\Models\Product;
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
            $products = DB::select('select products.id, products.deleted_at from products left join seo_metadata on products.id = seo_metadata.model_id where seo_metadata.model_id is null');
            foreach ($products as $product) {
                DB::table('seo_metadata')->insert([
                    'id' => Str::uuid(),
                    'global' => false,
                    'model_id' => $product->id,
                    'model_type' => Product::class,
                    'deleted_at' => $product->deleted_at !== null ? $product->deleted_at : null,
                    'created_at' => Carbon::now(),
                ]);
            }
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
