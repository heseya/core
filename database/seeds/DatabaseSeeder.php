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
    $this->call(Cleaner::class)
         ->call(ProductsSeeder::class)
         ->call(OrdersSeeder::class)
         ->call(UsersSeeder::class);
  }
}
