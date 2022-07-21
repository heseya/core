<?php

namespace Database\Seeders;

use App\Enums\RoleType;
use App\Models\Role;
use App\Models\SeoMetadata;
use App\Models\Status;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;

class InitSeeder extends Seeder
{
    public function run(): void
    {
        $this->createUser();
        $this->createStatuses();

        $seeder = new CountriesSeeder();
        $seeder->run();

        $this->createGlobalSeo();
    }

    private function createUser(): void
    {
        /** @var User $user */
        $user = User::factory()->create([
            'name' => 'Admin',
            'email' => 'admin@example.com',
            'password' => Hash::make('secret'),
        ]);

        $user->roles()->attach(
            Role::query()->where('type', RoleType::OWNER)->first()->getKey(),
        );
    }

    private function createStatuses(): void
    {
        Status::create([
            'name' => 'New',
            'color' => 'ffd600',
            'description' => 'Your order has been saved in system!',
            'order' => 1,
        ]);

        Status::create([
            'name' => 'Sent',
            'color' => '1faa00',
            'description' => 'The order has been shipped and it will be in your hands soon :)',
            'order' => 2,
        ]);

        Status::create([
            'name' => 'Canceled',
            'color' => 'a30000',
            'description' => 'Your order has been canceled, if this is mistake, please contact us.',
            'order' => 3,
            'cancel' => true,
        ]);
    }

    private function createGlobalSeo(): void
    {
        $seo = SeoMetadata::create([
            'global' => true,
        ]);
        Cache::put('seo.global', $seo);
    }
}
