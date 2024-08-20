<?php

use App\Enums\RoleType;
use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $role = Role::where('type', RoleType::UNAUTHENTICATED->value)->first();
        $role->revokePermissionTo([
            'auth.organization_register',
        ]);
        $role->save();

        Permission::query()->where('name', '=', 'auth.organization_register')->update(['name' => 'organizations.register']);

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $role = Role::where('type', RoleType::UNAUTHENTICATED->value)->first();
        $role->givePermissionTo([
            'auth.organization_register',
        ]);
        $role->save();
        Permission::query()->where('name', '=', 'organizations.register')->update(['name' => 'auth.organization_register']);
    }
};
