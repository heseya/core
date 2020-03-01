<?php

use App\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UsersSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $user = User::create([
            'name' => 'Jędrzej Buliński',
            'email' => 'jedrzej@heseya.com',
            'password' => Hash::make('secret'),
        ]);

        $user->givePermissionTo('manageUsers');
    }
}
