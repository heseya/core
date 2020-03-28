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
            'name' => 'Wojtek Testowy',
            'email' => 'admin@example.com',
            'password' => Hash::make('secret'),
        ]);

        $user->givePermissionTo('manageUsers');
        $user->givePermissionTo('manageStore');

        $user->givePermissionTo('viewProducts');
        $user->givePermissionTo('manageProducts');
        $user->givePermissionTo('createProducts');

        $user->givePermissionTo('viewOrders');
        $user->givePermissionTo('manageOrders');
        $user->givePermissionTo('createOrders');

        $user->givePermissionTo('viewChats');
        $user->givePermissionTo('replyChats');
        $user->givePermissionTo('createChats');

        $user->givePermissionTo('viewPages');
        $user->givePermissionTo('managePages');


        factory(User::class, 5)->create();
    }
}
