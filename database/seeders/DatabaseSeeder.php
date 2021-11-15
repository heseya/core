<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        $this
            ->call(ItemSeeder::class)
            ->call(ProductSetSeeder::class)
            ->call(ProductSeeder::class)
            ->call(ShippingMethodSeeder::class)
            ->call(OrderSeeder::class)
            ->call(PageSeeder::class)
            ->call(PermissionSeeder::class)
            ->call(UserSeeder::class)
            ->call(PackageTemplateSeeder::class)
            ->call(AuthSeeder::class)
            ->call(DiscountSeeder::class)
            ->call(SeoMetadataSeeder::class);
    }
}
