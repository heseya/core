<?php

namespace Database\Seeders;

use App\Enums\RoleType;
use App\Models\Role;
use App\Models\User;
use App\Models\UserPreference;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run(): void
    {
        User::factory()->count(5)->create();

        $authenticated = Role::where('type', RoleType::AUTHENTICATED)->firstOrFail();

        foreach (User::all() as $user) {
            $user->assignRole($authenticated);
            $user->preferences()->associate(UserPreference::create());
            $user->save();
        }
    }
}
