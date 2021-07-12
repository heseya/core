<?php

namespace Database\Seeders;

use App\Models\ProductSet;
use Illuminate\Database\Seeder;

class ProductSetSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run(): void
    {
        ProductSet::factory()->count(20)->create();
    }
}
