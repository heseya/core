<?php

use App\Models\Option;
use App\Models\Price;
use App\Models\Product;
use Domain\ProductSchema\Models\Schema;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::whereNull('product_id')->chunk(10, function (Collection $schemas) {
            /** @var Collection<int,Schema> $schemas */
            $schemas->each(fn (Schema $schema) => $schema->products->each(function (Product $product) use ($schema) {
                $copiedSchema = $schema->replicate();
                $copiedSchema->product_id = $product->id;
                $copiedSchema->save();

                $schema->options->each(function (Option $option) use ($copiedSchema) {
                    $copiedOption = $option->replicate();
                    $copiedOption->schema_id = $copiedSchema->id;
                    $copiedOption->save();

                    $option->prices->each(function (Price $price) use ($copiedOption) {
                        $copiedPrice = $price->replicate();
                        $copiedPrice->model_id = $copiedOption->id;
                        $copiedPrice->model_type = get_class($copiedOption);
                        $copiedPrice->save();
                    });
                });

                $schema->delete();
            }));
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
    }
};
