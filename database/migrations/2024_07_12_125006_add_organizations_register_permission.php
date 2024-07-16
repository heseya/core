<?php

use App\Enums\RoleType;
use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        Permission::create(['name' => 'auth.organization_register', 'display_name' => 'Możliwość rejestrowania organizacji']);

        $role = Role::where('type', RoleType::UNAUTHENTICATED->value)->first();
        $role->givePermissionTo([
            'auth.organization_register',
        ]);
        $role->save();
    }

    public function down(): void
    {
        $role = Role::where('type', RoleType::UNAUTHENTICATED->value)->first();
        $role->revokePermissionTo([
            'auth.organization_register',
        ]);
        $role->save();

        Permission::findByName('auth.organization_register')->delete();
    }
};
