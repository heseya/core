<?php

use App\Enums\RoleType;
use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration {
    public function up(): void
    {
        Role::all()->each(
            fn (Role $role) => $role->revokePermissionTo('auth.login'),
        );

        Permission::where('name', 'auth.login')->delete();
    }

    public function down(): void
    {
        Permission::create([
            'name' => 'auth.login',
            'display_name' => 'Możliwość logowania użytkownika',
        ]);

        Role::whereIn('type', [
                RoleType::OWNER,
                RoleType::AUTHENTICATED,
                RoleType::UNAUTHENTICATED,
            ])
            ->firstOrFail()
            ->givePermissionTo('auth.login');
    }
};
