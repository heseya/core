<?php

use App\ShippingMethod;
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
        factory(ShippingMethod::class, 3)->create(['public' => true]);
        factory(ShippingMethod::class)->create(['public' => false]);
    }
}
