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
            'name' => 'products.show_attachments_private',
            'display_name' => 'Możliwość wyświetlania prywatnych załączników produktów',
        ]);

        Role::query()
            ->where('type', RoleType::OWNER)
            ->firstOrFail()
            ->givePermissionTo('products.show_attachments_private');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Role::query()
            ->where('type', RoleType::OWNER)
            ->firstOrFail()
            ->revokePermissionTo('products.show_attachments_private');

        Permission::query()
            ->where('name', 'products.show_attachments_private')
            ->delete();
    }
};
