<?php

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Migrations\Migration;

class TfaPermissions extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Permission::create(['name' => 'users.2fa_remove', 'display_name' => 'Możliwość usuwania Two-Factor Authentication użytkownikom']);

        $owner = Role::findByName('Owner');
        $owner->givePermissionTo([
            'users.2fa_remove',
        ]);
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
        $owner->revokePermissionTo([
            'users.2fa_remove',
        ]);
        $owner->save();

        Permission::findByName('users.2fa_remove')->delete();
    }
}
