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

        Permission::create(['name' => 'manageUsers']);
    }
}
