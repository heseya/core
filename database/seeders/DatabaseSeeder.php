<?php

namespace Database\Seeders;

use App\Models\Language;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\App;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        App::setLocale(Language::where('default', true)->firstOrFail()->getKey());

        $this
            ->call(ItemSeeder::class)
            ->call(ProductSetSeeder::class)
            ->call(ProductSeeder::class)
            ->call(ShippingMethodSeeder::class)
            ->call(OrderSeeder::class)
            ->call(PageSeeder::class)
            ->call(UserSeeder::class)
            ->call(PackageTemplateSeeder::class)
            ->call(AuthSeeder::class)
            ->call(DiscountSeeder::class);
    }
}
