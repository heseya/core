<?php

use App\Enums\RoleType;
use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Migrations\Migration;

class AddMetadataPermissions extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Permission::create(['name' => 'orders.show_metadata_private', 'display_name' => 'Możliwość wyświetlania prywatnych metadanych zamówień']);
        Permission::create(['name' => 'page.show_metadata_private', 'display_name' => 'Możliwość wyświetlania prywatnych metadanych stron']);
        Permission::create(['name' => 'products.show_metadata_private', 'display_name' => 'Możliwość wyświetlania prywatnych metadanych produktów']);
        Permission::create(['name' => 'users.show_metadata_private', 'display_name' => 'Możliwość wyświetlania prywatnych metadanych użytkowników']);

        $owner = Role::query()->where('type', '=', RoleType::OWNER)->firstOrFail();
        $owner->givePermissionTo([
            'orders.show_metadata_private',
            'page.show_metadata_private',
            'products.show_metadata_private',
            'users.show_metadata_private',
        ]);
        $owner->save();
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        $owner = Role::query()->where('type', '=', RoleType::OWNER)->firstOrFail();
        $owner->revokePermissionTo([
            'orders.show_metadata_private',
            'page.show_metadata_private',
            'products.show_metadata_private',
            'users.show_metadata_private',
        ]);
        $owner->save();
    }
}
