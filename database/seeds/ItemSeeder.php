<?php

use App\Item;
use App\Deposit;
use Illuminate\Database\Seeder;

class ItemSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        factory(Item::class, 100)->create()->each(function ($item) {
            $item->deposits()->saveMany(factory(Deposit::class, rand(0, 2))->make());
        });
    }
}
