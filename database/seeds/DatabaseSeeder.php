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
        $this->call(ProductsSeeder::class)
            ->call(OrdersSeeder::class)
            ->call(PagesSeeder::class)
            ->call(RbacSeeder::class)
            ->call(UsersSeeder::class);
    }
}
