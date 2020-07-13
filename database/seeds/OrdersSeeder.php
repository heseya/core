<?php

use App\Models\Order;
use App\Models\Address;
use App\Models\OrderItem;
use App\Models\Payment;
use Illuminate\Database\Seeder;
use App\Models\ProductSchemaItem;

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
            $order->delivery_address_id = factory(Address::class)->create()->id;

            if (rand(0, 1)) {
                $order->invoice_address_id = factory(Address::class)->create()->id;
            }

            $order->save();

            $items = factory(OrderItem::class, rand(1, 3))->make();
            $order->items()->saveMany($items);

            $items->each(function ($item) {

                if ($item->product->schemas()->first()) {
                    $item->schemaItems()->attach($item->product->schemas()->first()->id);
                    $item->save();
                }
            });

            for ($i = 0; $i < rand(0, 5); $i++) {
                $order->payments()->save(factory(Payment::class)->make());
            }
        });
    }
}
