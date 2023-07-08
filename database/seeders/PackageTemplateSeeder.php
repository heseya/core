<?php

namespace Database\Seeders;

use App\Models\PackageTemplate;
use Illuminate\Database\Seeder;

class PackageTemplateSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        PackageTemplate::factory()->count(mt_rand(3, 6))->create();
    }
}
