<?php

use App\Models\Product;
use App\Models\ProductSet;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class CreateProductSetsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        Schema::create('product_sets', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->string('slug')->unique()->index();
            $table->uuid('parent_id')->nullable()->index();
            $table->boolean('public_parent')->default(true);
            $table->boolean('public')->default(true);
            $table->unsignedTinyInteger('order')->default(0);
            $table->boolean('hide_on_index')->default(false);
            $table->timestamps();
        });

        Schema::table('products', function (Blueprint $table) {
            $table->dropForeign('products_category_id_foreign');
            $table->dropForeign('products_brand_id_foreign');
        });

        Schema::create('product_set_product', function (Blueprint $table) {
            $table->uuid('product_id')->index();
            $table->uuid('product_set_id')->index();

            $table->primary(['product_id', 'product_set_id']);

            $table->foreign('product_id')->references('id')
                ->on('products')->onDelete('cascade');
            $table->foreign('product_set_id')->references('id')
                ->on('product_sets')->onDelete('cascade');
        });

        $this->moveSets('Categories', 'category_id', 0);
        $this->moveSets('Brands', 'brand_id', 1);

        Schema::table('products', function (Blueprint $table) {
            $table->foreign('category_id')->references('id')
                ->on('product_sets')->onDelete('set null');
            $table->foreign('brand_id')->references('id')
                ->on('product_sets')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropForeign('products_category_id_foreign');
            $table->dropForeign('products_brand_id_foreign');
        });
        Schema::dropIfExists('product_set_product');
        Schema::table('products', function (Blueprint $table) {
            $table->foreign('category_id')->references('id')
                ->on('categories')->onDelete('restrict');
            $table->foreign('brand_id')->references('id')
                ->on('brands')->onDelete('restrict');
        });
        Schema::dropIfExists('product_sets');
    }

    private function moveSets(string $set, string $idColumn, int $order): void
    {
        $children = DB::table(Str::of($set)->snake())->get();

        if ($children->isEmpty()) {
            return;
        }

        $parent = ProductSet::create([
            'name' => $set,
            'slug' => Str::of($set)->slug(),
            'public' => true,
            'order' => $order,
        ]);

        foreach ($children as $child) {
            $newSet = ProductSet::create([
                'name' => $child->name,
                'slug' => $child->slug,
                'parent_id' => $parent->getKey(),
                'public' => $child->public,
                'order' => $child->order,
                'hide_on_index' => $child->hide_on_index,
            ]);
            $newSet->id =  $child->id;
            $newSet->save();

            Product::where($idColumn, $newSet->getKey())->get()
                ->each(function ($product) use ($newSet) {
                   $product->sets()->syncWithoutDetaching($newSet->getKey());
                });
        }
    }
}
