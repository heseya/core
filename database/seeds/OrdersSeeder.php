<?php

use App\Models\Order;
use App\Models\Address;
use Illuminate\Database\Seeder;

class OrdersSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        factory(Order::class, 50)->create()->each(function ($order) {
            $order->deliveryAddress()->save(factory(Address::class)->make());

            if (rand(0, 1)) {
                $order->invoiceAddress()->save(factory(Address::class)->make());
            }
        });
    }
}
