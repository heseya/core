<?php

namespace Database\Seeders;

use App\Models\PackageTemplate;
use Illuminate\Database\Seeder;

class PackageTemplateSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run(): void
    {
        PackageTemplate::factory()->count(rand(3, 6))->create();
    }
}
