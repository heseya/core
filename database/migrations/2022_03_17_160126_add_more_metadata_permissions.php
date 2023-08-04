<?php

use App\Enums\RoleType;
use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Migrations\Migration;

class AddMoreMetadataPermissions extends Migration
{
    public function up(): void
    {
        Permission::create(['name' => 'schemas.show_metadata_private', 'display_name' => 'Możliwość wyświetlania prywatnych metadanych schematów']);
        Permission::create(['name' => 'options.show_metadata_private', 'display_name' => 'Możliwość wyświetlania prywatnych metadanych stron']);
        Permission::create(['name' => 'product_sets.show_metadata_private', 'display_name' => 'Możliwość wyświetlania prywatnych metadanych zestawów produktów']);
        Permission::create(['name' => 'discounts.show_metadata_private', 'display_name' => 'Możliwość wyświetlania prywatnych metadanych kuponów']);
        Permission::create(['name' => 'items.show_metadata_private', 'display_name' => 'Możliwość wyświetlania prywatnych metadanych przedmiotów']);
        Permission::create(['name' => 'statuses.show_metadata_private', 'display_name' => 'Możliwość wyświetlania prywatnych metadanych statusów']);
        Permission::create(['name' => 'shipping_methods.show_metadata_private', 'display_name' => 'Możliwość wyświetlania prywatnych metadanych metod dostawy']);
        Permission::create(['name' => 'packages.show_metadata_private', 'display_name' => 'Możliwość wyświetlania prywatnych metadanych szablonów paczek']);
        Permission::create(['name' => 'roles.show_metadata_private', 'display_name' => 'Możliwość wyświetlania prywatnych metadanych ról']);
        Permission::create(['name' => 'apps.show_metadata_private', 'display_name' => 'Możliwość wyświetlania prywatnych metadanych aplikacji']);
        Permission::create(['name' => 'media.show_metadata_private', 'display_name' => 'Możliwość wyświetlania prywatnych metadanych mediów']);

        $owner = Role::query()->where('type', '=', RoleType::OWNER->value)->firstOrFail();
        $owner->givePermissionTo([
            'schemas.show_metadata_private',
            'options.show_metadata_private',
            'product_sets.show_metadata_private',
            'discounts.show_metadata_private',
            'items.show_metadata_private',
            'statuses.show_metadata_private',
            'shipping_methods.show_metadata_private',
            'packages.show_metadata_private',
            'roles.show_metadata_private',
            'apps.show_metadata_private',
            'media.show_metadata_private',
        ]);
        $owner->save();
    }

    public function down(): void
    {
        $permissions = [
            'schemas.show_metadata_private',
            'options.show_metadata_private',
            'product_sets.show_metadata_private',
            'discounts.show_metadata_private',
            'items.show_metadata_private',
            'statuses.show_metadata_private',
            'shipping_methods.show_metadata_private',
            'packages.show_metadata_private',
            'roles.show_metadata_private',
            'apps.show_metadata_private',
            'media.show_metadata_private',
        ];

        $owner = Role::query()->where('type', '=', RoleType::OWNER->value)->firstOrFail();
        $owner->revokePermissionTo($permissions);
        $owner->save();

        foreach ($permissions as $permission) {
            Permission::query()
                ->where('name', '=', $permission)
                ->delete();
        }
    }
}
