<?php

use App\Enums\RoleType;
use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Role::all()->each(function (Role $role): void {
            $role->revokePermissionTo('auth.sessions.revoke');
            $role->revokePermissionTo('auth.sessions.show');
        });

        Permission::whereIn('name', ['auth.sessions.revoke', 'auth.sessions.show'])->delete();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Permission::create([
            'name' => 'auth.sessions.revoke',
            'display_name' => 'Możliwość blokowania sesji użytkownika',
        ]);

        Permission::create([
            'name' => 'auth.sessions.show',
            'display_name' => 'Dostęp do listy sesji zalogowanego użytkownika',
        ]);

        Role::whereIn('type', [
            RoleType::OWNER->value,
            RoleType::AUTHENTICATED->value,
        ])
            ->get()
            ->each(fn (Role $role) => $role->givePermissionTo(['auth.sessions.revoke', 'auth.sessions.show']));
    }
};
