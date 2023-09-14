<?php

use App\Enums\RoleType;
use App\Models\Role;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Role::query()
            ->whereNot('type', RoleType::OWNER)
            ->each(function (Role $role): void {
                $role->givePermissionTo([
                    'redirects.show',
                ]);
            });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Role::query()
            ->whereNot('type', RoleType::OWNER)
            ->each(function (Role $role): void {
                $role->revokePermissionTo([
                    'redirects.show',
                ]);
            });
    }
};
