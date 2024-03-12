<?php

declare(strict_types=1);

namespace Database\Seeders;

use Domain\Setting\Models\Setting;
use Illuminate\Database\Seeder;

/**
 * Seeder for faster developing of default heseya store template.
 */
class DefaultTemplateSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Setting::query()->create([
            'name' => 'root_category_slug',
            'value' => 'all',
            'public' => true,
        ]);
    }
}
