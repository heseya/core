<?php

namespace Database\Seeders;

use App\Enums\RoleType;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        User::factory()->create([
            'name' => 'Wojtek Testowy',
            'email' => 'admin@example.com',
            'password' => Hash::make('secret'),
        ])->assignRole(
            Role::where('type', RoleType::OWNER)->firstOrFail(),
        );

        User::factory()->count(5)->create();
    }
}
