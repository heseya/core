<?php

use App\Enums\RoleType;
use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        Permission::create([
            'name' => 'consents.show_hidden',
            'display_name' => 'Dostęp do ukrytych zgód',
        ]);

        Permission::create([
            'name' => 'banners.show_hidden',
            'display_name' => 'Dostęp do ukrytych banerów',
        ]);

        Role::query()
            ->where('type', RoleType::OWNER->value)
            ->firstOrFail()
            ->givePermissionTo(['consents.show_hidden', 'banners.show_hidden']);
    }

    public function down(): void
    {
        Role::query()
            ->where('type', RoleType::OWNER->value)
            ->firstOrFail()
            ->revokePermissionTo(['consents.show_hidden', 'banners.show_hidden']);

        Permission::query()
            ->where('name', 'consents.show_hidden')
            ->delete();
        Permission::query()
            ->where('name', 'banners.show_hidden')
            ->delete();
    }
};