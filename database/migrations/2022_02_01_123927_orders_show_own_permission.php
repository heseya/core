<?php

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Migrations\Migration;

class OrdersShowOwnPermission extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Permission::create(['name' => 'orders.show_own', 'display_name' => 'Dostęp do listy zamówień zalogowanego użytkownika']);

        $owner = Role::findByName('Owner');
        $owner->givePermissionTo(['orders.show_own']);
        $owner->save();
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        $owner = Role::findByName('Owner');
        $owner->revokePermissionTo(['orders.show_own']);
        $owner->save();

        Permission::findByName('orders.show_own')->delete();
    }
}
