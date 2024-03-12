<?php

use App\Enums\RoleType;
use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Migrations\Migration;

class TfaPermissions extends Migration
{
    public function up(): void
    {
        Permission::create(['name' => 'users.2fa_remove', 'display_name' => 'Możliwość usuwania Two-Factor Authentication użytkownikom']);

        $owner = Role::where('type', RoleType::OWNER->value)->first();
        $owner->givePermissionTo([
            'users.2fa_remove',
        ]);
        $owner->save();
    }

    public function down(): void
    {
        $owner = Role::where('type', RoleType::OWNER->value)->first();
        $owner->revokePermissionTo([
            'users.2fa_remove',
        ]);
        $owner->save();

        Permission::findByName('users.2fa_remove')->delete();
    }
}
