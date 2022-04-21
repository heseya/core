<?php

namespace Database\Seeders;

use App\Models\Deposit;
use App\Models\Item;
use Illuminate\Database\Seeder;

class ItemSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run(): void
    {
        Item::factory()
            ->has(Deposit::factory()->count(rand(0, 2)))
            ->count(100)
            ->create();
    }
}
