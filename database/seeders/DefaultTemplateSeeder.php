<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\RoleType;
use App\Models\Role;
use App\Models\Setting;
use App\Models\User;
use App\Models\UserPreference;
use Domain\Language\Language;
use Domain\Metadata\Enums\MetadataType;
use Domain\ProductSet\ProductSet;
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
