<?php

namespace Database\Seeders;

use App\Models\SeoMetadata;
use Illuminate\Database\Seeder;

class SeoMetadataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $seo = SeoMetadata::where('global', '=', true)->first();
        if ($seo === null) {
            SeoMetadata::create([
                'global' => true,
            ]);
        }
    }
}
