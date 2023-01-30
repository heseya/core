<?php

namespace Database\Seeders;

use App\Models\ProductSet;
use App\Models\SeoMetadata;
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
        ProductSet::factory()->count(20)->create()->each(function ($set): void {
            $rand = rand(0, 4);
            $this->seo($set);
            if ($rand === 0) {
                $sets = ProductSet::factory([
                    'parent_id' => $set->getKey(),
                ])->count(rand(1, 2))->create();
                $sets->each(fn ($newSet) => $this->seo($newSet));
            } elseif ($rand === 1) {
                $raw = ProductSet::factory()->raw();

                $newSet = ProductSet::factory([
                    'parent_id' => $set->getKey(),
                    'name' => $raw['name'],
                    'slug' => $set->slug . '-' . $raw['slug'],
                ])->create();
                $this->seo($newSet);
            }
        });
    }

    private function seo(ProductSet $set): void
    {
        $seo = SeoMetadata::factory()->create();
        $set->seo()->save($seo);
    }
}
