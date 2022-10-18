<?php

namespace Database\Seeders;

use App\Models\Deposit;
use App\Models\Item;
use App\Models\OrderProduct;
use Illuminate\Database\Seeder;

class DepositSeeder extends Seeder
{
    public function run(): void
    {
        foreach (OrderProduct::query()->select('id')->limit(10)->cursor() as $product) {
            Deposit::factory()->create([
                'order_product_id' => $product->getKey(),
                'quantity' => -1 * rand(1, 5),
                'item_id' => Item::query()->select('id')->inRandomOrder()->first()->getKey(),
            ]);
        }
    }
}
