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
        Permission::create([
            'name' => 'users.self_remove',
            'display_name' => 'Możliwość usunięcia własnego konta',
        ]);

        Role::where('type', RoleType::AUTHENTICATED)
            ->firstOrFail()
            ->givePermissionTo('users.self_remove');

        Role::where('type', RoleType::OWNER)
            ->firstOrFail()
            ->givePermissionTo('users.self_remove');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Role::where('type', RoleType::AUTHENTICATED)
            ->firstOrFail()
            ->revokePermissionTo('users.self_remove');

        Role::where('type', RoleType::OWNER)
            ->firstOrFail()
            ->revokePermissionTo('users.self_remove');

        Permission::query()
            ->where('name', 'users.self_remove')
            ->delete();
    }
};
