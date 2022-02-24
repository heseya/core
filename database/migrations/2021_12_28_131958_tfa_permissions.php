<?php

use App\Enums\RoleType;
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

        $owner = Role::where('type', RoleType::OWNER)->first();
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
        $owner = Role::where('type', RoleType::OWNER)->first();
        $owner->revokePermissionTo([
            'users.2fa_remove',
        ]);
        $owner->save();

        Permission::findByName('users.2fa_remove')->delete();
    }
}
