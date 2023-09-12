<?php

namespace Database\Seeders;

use App\Enums\ShippingType;
use App\Models\Address;
use App\Models\Order;
use App\Models\OrderProduct;
use App\Models\OrderProductUrl;
use App\Models\OrderSchema;
use App\Models\Payment;
use App\Models\Product;
use App\Models\Status;
use App\Services\Contracts\OrderServiceContract;
use App\Services\OrderService;
use Brick\Money\Money;
use Domain\ShippingMethod\Models\ShippingMethod;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\App;

class OrderSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $shipping_methods = ShippingMethod::whereNot('shipping_type', ShippingType::DIGITAL)->get();
        $digital_methods = ShippingMethod::where('shipping_type', ShippingType::DIGITAL)->get();
        $statuses = Status::all();

        /** @var OrderService $orderService */
        $orderService = App::make(OrderServiceContract::class);

        Order::factory()
            ->count(50)
            ->create()
            ->each(
                function ($order) use ($shipping_methods, $statuses, $orderService, $digital_methods): void {
                    if (mt_rand(0, 1)) {
                        $digital_shipping_method = $digital_methods->random();
                    } else {
                        $digital_shipping_method = null;
                    }

                    if (mt_rand(0, 1) || !$digital_shipping_method) {
                        $shipping_method = $shipping_methods->random();
                    } else {
                        $shipping_method = null;
                    }

                    $order->shipping_method_id = $shipping_method?->getKey();
                    $order->digital_shipping_method_id = $digital_shipping_method?->getKey();
                    $order->status_id = $statuses->random()->getKey();

                    if ($shipping_method && in_array(
                            $shipping_method->shipping_type,
                            [ShippingType::ADDRESS, ShippingType::POINT]
                        )) {
                        $order->shipping_address_id = Address::factory()->create()->getKey();
                    } elseif ($shipping_method && $shipping_method->shipping_type === ShippingType::POINT_EXTERNAL) {
                        $order->shipping_place = fake()->streetAddress();
                    }

                    if (mt_rand(0, 1)) {
                        $order->billing_address_id = Address::factory()->create()->getKey();
                    } else {
                        $order->billing_address_id = $order->shipping_address_id ?? Address::factory()->create(
                        )->getKey();
                    }

                    $order->shipping_type = $shipping_method->shipping_type ?? $digital_shipping_method->shipping_type;
                    $order->save();

                    if ($digital_shipping_method !== null) {
                        $this->addProductsToOrder($order, true);
                    }

                    if ($shipping_method !== null) {
                        $this->addProductsToOrder($order, false);
                    }

                    $order = Order::query()->findOrFail($order->getKey());

                    $summary = $orderService->calcSummary($order);
                    $cart_total = $summary->plus($order->shipping_price);

                    $order->update([
                        'summary' => $summary,
                        'cart_total' => $cart_total,
                        'cart_total_initial' => $cart_total,
                    ]);

                    for ($i = 0; $i < mt_rand(0, 5); ++$i) {
                        $order->payments()->save(Payment::factory(['currency' => $order->currency])->make());
                    }
                }
            );
    }

    private function addProductsToOrder(Order $order, bool $digital): void
    {
        $products = OrderProduct::factory()
            ->count(mt_rand(1, 3))
            ->state(
                fn ($sequence) => [
                    'product_id' => Product::where('shipping_digital', $digital)->inRandomOrder()->first()->getKey(),
                    'shipping_digital' => $digital,
                ]
            )
            ->make([
                'currency' => $order->currency,
            ]);
        $order->products()->saveMany($products);

        $products->each(function (OrderProduct $product) use ($digital): void {
            if (mt_rand(0, 3) === 0) {
                $schemas = OrderSchema::factory()->count(mt_rand(1, 3))->make([
                    'currency' => $product->currency,
                ]);
                $product->schemas()->saveMany($schemas);

                $sum = $product->price_initial->plus(
                    $product->schemas()->get()->reduce(
                        fn (Money $carry, OrderSchema $schema) => $carry->plus($schema->price),
                        Money::zero($product->currency->value),
                    ),
                );
                $product->update([
                    'price_initial' => $sum,
                    'price' => $sum,
                ]);
            }

            if ($digital && mt_rand(0, 1)) {
                $product->urls()->createMany(OrderProductUrl::factory()->count(mt_rand(1, 3))->make()->toArray());
            }
        });
    }
}
