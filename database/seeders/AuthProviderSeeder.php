<?php

namespace Database\Seeders;

use App\Enums\AuthProviderKey;
use App\Models\AuthProvider;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;

class AuthProviderSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run(): void
    {
        $enums = Collection::make(AuthProviderKey::getInstances());

        $enums->each(function (AuthProviderKey $enum) {
            AuthProvider::factory()->create([
                'key' => $enum->value,
                'active' => false,
                'client_id' => null,
                'client_secret' => null,
            ]);
        });
    }
}
