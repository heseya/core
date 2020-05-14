<?php

use App\Models\User;
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
            'name' => 'Wojtek Testowy',
            'email' => 'admin@example.com',
            'password' => Hash::make('secret'),
        ]);

        factory(User::class, 5)->create();
    }
}
