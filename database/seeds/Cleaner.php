<?php

use Illuminate\Database\Seeder;

use App\Order;
use App\Address;
use App\Item;
use App\Product;
use App\ProductCategory;

use App\User;

class Cleaner extends Seeder {
  /**
   * Run the database seeds.
   *
   * @return void
   */
  public function run() {

    DB::statement('SET FOREIGN_KEY_CHECKS=0;');

    Order::truncate();
    Address::truncate();
    Item::truncate();
    Product::truncate();
    ProductCategory::truncate();

    User::truncate();

    DB::statement('SET FOREIGN_KEY_CHECKS=1;');
  }
}
