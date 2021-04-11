<?php

namespace Database\Seeders;

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
        ShippingMethod::factory()->count(3)->create(['public' => true])->each(function ($shipping_method) {

            $payment_methods = PaymentMethod::factory()->count(rand(1, 3))->create();
            $shipping_method->paymentMethods()->sync($payment_methods);
        });

        ShippingMethod::factory()->create(['public' => false]);
    }
}
