<?php

use App\Models\Product;
use App\Models\Schema;
use Carbon\Carbon;
use Illuminate\Database\Migrations\Migration;
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::with('products')->each(static function(Schema $schema) {
            if ($schema->products->count() > 1) {
                $first = true;
                foreach ($schema->products as $product) {
                    if ($first) {
                        $first = false;
                        continue;
                    }

                    $now = Carbon::now();

                    $duplicate = $schema->replicate();
                    $duplicate->product()->associate($product);
                    $duplicate->created_at = $now;
                    $duplicate->updated_at = $now;
                    $duplicate->save();

                    if ($schema->options->count() > 1) {
                        foreach ($schema->options as $option) {
                            $optionDuplicate = $option->replicate();
                            $optionDuplicate->schema()->associate($duplicate);
                            $optionDuplicate->created_at = $now;
                            $optionDuplicate->updated_at = $now;
                            $optionDuplicate->save();
                        }
                    }

                    $schema->products()->detach($product->getKey());
                }
            } elseif ($schema->products->count() === 1) {
                $schema->product()->associate($schema->products->first());
                $schema->save();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
