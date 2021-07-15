<?php

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
            $table->boolean('public')->default(true);
            $table->unsignedTinyInteger('order');
            $table->boolean('hide_on_index')->default(false);
            $table->timestamps();

            $table->unique(['parent_id', 'order']);
        });

        $this->moveSets('Categories');
        $this->moveSets('Brands');

        Schema::create('product_set_product', function (Blueprint $table) {
            $table->uuid('product_id')->index();
            $table->uuid('product_set_id')->index();

            $table->primary(['product_id', 'product_set_id']);

            $table->foreign('product_id')->references('id')->on('products')->onDelete('cascade');
            $table->foreign('product_set_id')->references('id')->on('product_sets')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('product_sets');
    }

    private function moveSets(string $set): void
    {
        $children = DB::table(Str::of($set)->snake())->get();

        if ($children->isEmpty()) {
            return;
        }

        $parent = ProductSet::create([
            'name' => $set,
            'slug' => Str::of($set)->slug(),
            'public' => true,
        ]);

        foreach ($children as $child) {
            ProductSet::create([
                'id' => $child->getKey(),
                'name' => $child->name,
                'slug' => $child->slug,
                'parent_id' => $parent->getKey(),
                'public' => $child->public,
                'order' => $child->order,
                'hide_on_index' => $child->hide_on_index,
            ]);
        }
    }
}
