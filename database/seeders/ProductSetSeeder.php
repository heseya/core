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
        ProductSet::factory()->count(20)->create()->each(function ($set) {
            $rand = rand(0, 4);
            if ($rand === 0) {
                ProductSet::factory([
                    'parent_id' => $set->getKey(),
                ])->count(rand(1, 2))->create();
            } else if ($rand === 1) {
                $raw = ProductSet::factory()->raw();

                ProductSet::factory([
                    'parent_id' => $set->getKey(),
                    'name' => $raw['name'],
                    'slug' =>  $set->slug . '-' . $raw['slug'],
                ])->create();
            }
        });
    }
}
