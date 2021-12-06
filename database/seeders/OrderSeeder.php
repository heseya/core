<?php

namespace Database\Seeders;

use App\Models\Address;
use App\Models\Order;
use App\Models\OrderProduct;
use App\Models\OrderSchema;
use App\Models\Payment;
use App\Models\Schema;
use App\Models\ShippingMethod;
use App\Models\Status;
use App\Services\Contracts\OrderServiceContract;
use App\Services\OrderService;
use Illuminate\Database\Seeder;

class OrderSeeder extends Seeder
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

        /** @var OrderService $orderService */
        $orderService = app(OrderServiceContract::class);

        Order::factory()->count(50)->create()->each(function ($order) use ($shipping_methods, $statuses, $orderService) {

            $order->shipping_method_id = $shipping_methods->random()->getKey();
            $order->status_id = $statuses->random()->getKey();

            $order->delivery_address_id = Address::factory()->create()->getKey();

            if (rand(0, 1)) {
                $order->invoice_address_id = Address::factory()->create()->getKey();
            }

            $order->save();

            $products = OrderProduct::factory()->count(rand(1, 3))->make();
            $order->products()->saveMany($products);

            $products->each(function ($product) {
                if (rand(0, 3) === 0) {
                    $schemas = OrderSchema::factory()->count(rand(1, 3))->make();
                    $product->schemas()->saveMany($schemas);
                }
            });

            $order->update([
                'summary' => $orderService->calcSummary($order),
            ]);

            for ($i = 0; $i < rand(0, 5); $i++) {
                $order->payments()->save(Payment::factory()->make());
            }
        });
    }
}
