<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this
            ->call(InitSeeder::class)
            ->call(ItemSeeder::class)
            ->call(ProductSetSeeder::class)
            ->call(ProductSeeder::class)
            ->call(ShippingMethodSeeder::class)
            ->call(OrderSeeder::class)
            ->call(PageSeeder::class)
            ->call(UserSeeder::class)
            ->call(DiscountSeeder::class)
            ->call(WebHookEventLogSeeder::class)
            ->call(DepositSeeder::class);
    }
}
