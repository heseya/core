<?php

use App\Enums\RoleType;
use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Migrations\Migration;

class AddProfilePermissionAndAddItToAuthenticatedRole extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Saved Addresses
        Permission::create(['name' => 'profile.addresses_manage', 'display_name' => 'Możliwość zarządzania swoimi zapisanymi adresami']);

        $authenticated = Role::where('type', RoleType::AUTHENTICATED)->firstOrFail();
        $authenticated->givePermissionTo('profile.addresses_manage');
        $authenticated->save();

        $owner = Role::where('type', RoleType::OWNER)->first();
        $owner->givePermissionTo([
            'profile.addresses_manage',
        ]);
        $owner->save();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $authenticated = Role::where('type', RoleType::AUTHENTICATED)->firstOrFail();
        $authenticated->revokePermissionTo('profile.addresses_manage');
        $authenticated->save();

        $owner = Role::where('type', RoleType::OWNER)->first();
        $owner->revokePermissionTo([
            'profile.addresses_manage',
        ]);
        $owner->save();

        Permission::findByName('profile.addresses_manage')->delete();
//        Role::delete();
    }
}
