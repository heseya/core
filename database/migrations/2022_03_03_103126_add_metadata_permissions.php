<?php

use App\Enums\RoleType;
use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Migrations\Migration;

class AddMetadataPermissions extends Migration
{
    public function up(): void
    {
        Permission::create(['name' => 'orders.show_metadata_private', 'display_name' => 'Możliwość wyświetlania prywatnych metadanych zamówień']);
        Permission::create(['name' => 'pages.show_metadata_private', 'display_name' => 'Możliwość wyświetlania prywatnych metadanych stron']);
        Permission::create(['name' => 'products.show_metadata_private', 'display_name' => 'Możliwość wyświetlania prywatnych metadanych produktów']);
        Permission::create(['name' => 'users.show_metadata_private', 'display_name' => 'Możliwość wyświetlania prywatnych metadanych użytkowników']);

        $owner = Role::query()->where('type', '=', RoleType::OWNER->value)->firstOrFail();
        $owner->givePermissionTo([
            'orders.show_metadata_private',
            'pages.show_metadata_private',
            'products.show_metadata_private',
            'users.show_metadata_private',
        ]);
        $owner->save();
    }

    public function down(): void
    {
        $owner = Role::query()->where('type', '=', RoleType::OWNER->value)->firstOrFail();
        $owner->revokePermissionTo([
            'orders.show_metadata_private',
            'pages.show_metadata_private',
            'products.show_metadata_private',
            'users.show_metadata_private',
        ]);
        $owner->save();
    }
}
