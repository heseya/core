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
            $shipping_method->paymentMethods()->saveMany(factory(PaymentMethod::class, rand(1, 3))->make());
        });
        factory(ShippingMethod::class)->create(['public' => false]);
    }
}
