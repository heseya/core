<?php

namespace Database\Seeders;

use App\Models\Address;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\ShippingMethod;
use App\Models\Status;
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
        $shipping_methods = ShippingMethod::all();
        $statuses = Status::all();

        Order::factory()->count(50)->create()->each(function ($order) use ($shipping_methods, $statuses) {

            $order->shipping_method_id = $shipping_methods->random()->getKey();
            $order->status_id = $statuses->random()->getKey();

            $order->delivery_address_id = Address::factory()->create()->getKey();

            if (rand(0, 1)) {
                $order->invoice_address_id = Address::factory()->create()->getKey();
            }

            $order->save();

            $items = OrderItem::factory()->count(rand(1, 3))->make();
            $order->items()->saveMany($items);

            $items->each(function ($item) {

//                $schema = $item->product->schemas()->inRandomOrder()->first();
//
//                if ($schema) {
//                    $item->schemaItems()->sync($schema->getKey());
//                    $item->save();
//                }
            });

            for ($i = 0; $i < rand(0, 5); $i++) {
                $order->payments()->save(Payment::factory()->make());
            }
        });
    }
}
