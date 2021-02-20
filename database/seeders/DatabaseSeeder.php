<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        $this
            ->call(ItemSeeder::class)
            ->call(ProductSeeder::class)
            ->call(ShippingMethodSeeder::class)
            ->call(OrderSeeder::class)
            ->call(PageSeeder::class)
            ->call(UserSeeder::class)
            ->call(PackageTemplateSeeder::class)
            ->call(AuthSeeder::class);
    }
}