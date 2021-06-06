<?php

namespace Database\Seeders;

use App\Models\Discount;
use App\Models\Order;
use Illuminate\Database\Seeder;

class DiscountSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $discounts = Discount::factory()->count(25)->create();

        foreach (Order::inRandomOrder()->limit(40)->get() as $order) {
            $discount = $discounts->random();

            if (!$discount->available) {
                $discount->increment('max_uses', 24);
            }

            $order->discounts()->attach($discount, [
                'discount' => $discount->discount,
                'type' => $discount->type,
            ]);
        }
    }
}
