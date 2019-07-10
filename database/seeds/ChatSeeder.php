<?php

use Illuminate\Database\Seeder;

use App\Chat;

class ChatSeeder extends Seeder
{
  /**
   * Run the database seeds.
   *
   * @return void
   */
  public function run()
  {
    $faker = \Faker\Factory::create('pl_PL');

    for ($i = 1; $i <= 20; $i++) {

      $order = Chat::create([
        'type' => 0,
      ]);
    }
  }
}
