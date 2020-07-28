<?php

use App\Models\PaymentMethod;
use App\Models\ShippingMethod;
use Illuminate\Database\Seeder;

class ShippingMethodSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        factory(ShippingMethod::class, 3)->create(['public' => true])->each(function ($shipping_method) {

            $payment_methods = factory(PaymentMethod::class, rand(1, 3))->create();
            $shipping_method->paymentMethods()->sync($payment_methods);
        });
        factory(ShippingMethod::class)->create(['public' => false]);
    }
}
