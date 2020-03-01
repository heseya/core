<?php

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;

class RbacSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Permission::create(['name' => 'viewProducts']);
        Permission::create(['name' => 'manageProducts']);
        Permission::create(['name' => 'createProducts']);

        Permission::create(['name' => 'viewOrders']);
        Permission::create(['name' => 'manageOrders']);
        Permission::create(['name' => 'createOrders']);

        Permission::create(['name' => 'viewChats']);
        Permission::create(['name' => 'replyChats']);
        Permission::create(['name' => 'createChats']);

        Permission::create(['name' => 'viewPages']);
        Permission::create(['name' => 'managePages']);
        Permission::create(['name' => 'createPages']);

        Permission::create(['name' => 'manageUsers']);
        Permission::create(['name' => 'manageStore']);
    }
}
