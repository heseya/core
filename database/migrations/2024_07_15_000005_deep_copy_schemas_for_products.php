<?php

use Domain\ProductSchema\Models\Schema\Schema;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::whereNull('product_id')->chunk(10, function (array $schemas) {
            /** @var Schema[] $schemas */
            foreach ($schemas as $schema) {
                foreach ($schema->products() as $product) {
                    $copiedSchema = $schema->replicate();
                    $copiedSchema->product_id = $product->id;
                    $copiedSchema->save();

                    foreach ($schema->options as $option) {
                        $copiedOption = $option->replicate();
                        $copiedOption->schema_id = $copiedSchema->id;
                        $copiedOption->save();

                        foreach ($option->prices as $price) {
                            $copiedPrice = $price->replicate();
                            $copiedPrice->model_id = $copiedOption->id;
                            $copiedPrice->model_type = get_class($copiedOption);
                            $copiedPrice->save();
                        }
                    }
                }

                $schema->delete();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
    }
};
