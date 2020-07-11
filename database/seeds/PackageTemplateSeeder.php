<?php

use App\Models\PackageTemplate;
use Illuminate\Database\Seeder;

class PackageTemplateSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        factory(PackageTemplate::class, rand(3, 6))->create();
    }
}
