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
