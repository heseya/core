<?php

namespace Database\Seeders;

use App\Enums\AuthProviderKey;
use App\Enums\RoleType;
use App\Models\AuthProvider;
use App\Models\Role;
use App\Models\SeoMetadata;
use App\Models\Status;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;
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
        $this->createAuthProviders();
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
        Status::query()->create([
            'name' => 'New',
            'color' => 'ffd600',
            'description' => 'Your order has been saved in system!',
            'order' => 1,
        ]);

        Status::query()->create([
            'name' => 'Sent',
            'color' => '1faa00',
            'description' => 'The order has been shipped and it will be in your hands soon :)',
            'order' => 2,
        ]);

        Status::query()->create([
            'name' => 'Canceled',
            'color' => 'a30000',
            'description' => 'Your order has been canceled, if this is mistake, please contact us.',
            'order' => 3,
            'cancel' => true,
        ]);
    }

    private function createGlobalSeo(): void
    {
        $seo = SeoMetadata::query()->create([
            'global' => true,
        ]);
        Cache::put('seo.global', $seo);
    }

    private function createAuthProviders(): void
    {
        $enums = Collection::make(AuthProviderKey::getInstances());
        $enums->each(fn (AuthProviderKey $enum) => AuthProvider::factory()->create([
            'key' => $enum->value,
            'active' => false,
            'client_id' => null,
            'client_secret' => null,
        ]));
    }
}
