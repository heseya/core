<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Ramsey\Uuid\Uuid;

return new class extends Migration {
    public function up(): void
    {
        $newPriceRanges = DB::table('price_ranges')->lazyById()->map(fn (object $priceRange) => [
            'id' => Uuid::uuid4(),
            'shipping_method_id' => $priceRange->shipping_method_id,
            'start' => $priceRange->start,
            'value' => '0',
            'currency' => 'GBP',
        ])->toArray();

        DB::table('price_ranges')->insert($newPriceRanges);
        unset($newPriceRanges);

        $newPrices = DB::table('prices')->lazyById()->map(fn (object $price) => [
            'id' => Uuid::uuid4(),
            'model_id' => $price->model_id,
            'model_type' => $price->model_type,
            'price_type' => $price->price_type,
            'value' => '0',
            'currency' => 'GBP',
            'is_net' => $price->is_net,
        ])->toArray();

        DB::table('prices')->insert($newPrices);
    }

    public function down(): void
    {
        DB::table('price_ranges')->where('currency', 'GBP')->delete();
        DB::table('prices')->where('currency', 'GBP')->delete();
    }
};
