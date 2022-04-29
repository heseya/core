<?php

namespace Database\Seeders;

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
//        $this->createUser();
        $this->createStatuses();

        $seeder = new CountriesSeeder();
        $seeder->run();

        $this->createGlobalSeo();
    }

    private function createUser(): void
    {
        User::factory()->create([
            'name' => 'Admin',
            'email' => 'admin@example.com',
            'password' => Hash::make('secret'),
        ]);
    }

    private function createStatuses(): void
    {
        Status::create([
            'name' => 'New',
            'color' => 'ffd600',
            'description' => 'Your order has been saved in system!',
        ]);

        Status::create([
            'name' => 'Sent',
            'color' => '1faa00',
            'description' => 'The order has been shipped and it will be in your hands soon :)',
        ]);

        Status::create([
            'name' => 'Canceled',
            'color' => 'a30000',
            'description' => 'Your order has been canceled, if this is mistake, please contact us.',
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
