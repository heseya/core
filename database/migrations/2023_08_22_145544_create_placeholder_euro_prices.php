<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Ramsey\Uuid\Uuid;

return new class extends Migration {
    const NEW_CURRENCY = 'GBP';

    public function up(): void
    {
        DB::transaction(function () {
            DB::table('price_ranges')->where('currency', '!=', $this::NEW_CURRENCY)->chunkById(100, function ($chunk) {
                $newPriceRanges = $chunk->map(fn (object $priceRange) => [
                    'id' => Uuid::uuid4(),
                    'shipping_method_id' => $priceRange->shipping_method_id,
                    'start' => $priceRange->start,
                    'value' => '0',
                    'currency' => $this::NEW_CURRENCY,
                ])->toArray();

                DB::table('price_ranges')->insert($newPriceRanges);
            });

            DB::table('prices')->where('currency', '!=', $this::NEW_CURRENCY)->chunkById(100, function ($chunk) {
                $newPrices = $chunk->map(fn (object $price) => [
                    'id' => Uuid::uuid4(),
                    'model_id' => $price->model_id,
                    'model_type' => $price->model_type,
                    'price_type' => $price->price_type,
                    'value' => '0',
                    'currency' => $this::NEW_CURRENCY,
                    'is_net' => $price->is_net,
                ])->toArray();

                DB::table('prices')->insert($newPrices);
            });
        });
    }

    public function down(): void
    {
        DB::table('price_ranges')->where('currency', $this::NEW_CURRENCY)->delete();
        DB::table('prices')->where('currency', $this::NEW_CURRENCY)->delete();
    }
};
