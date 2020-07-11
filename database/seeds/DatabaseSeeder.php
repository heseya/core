<?php

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
            ->call(ProductsSeeder::class)
            ->call(ShippingMethodSeeder::class)
            ->call(OrdersSeeder::class)
            ->call(PagesSeeder::class)
            ->call(UsersSeeder::class)
            ->call(ChatSeeder::class)
            ->call(PackageTemplateSeeder::class);
    }
}
