<?php

use App\Enums\RoleType;
use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Permission::create(['name' => 'attributes.show_metadata_private', 'display_name' => 'Możliwość wyświetlania prywatnych metadanych atrybutów oraz opcji atrybutów']);

        $owner = Role::query()->where('type', '=', RoleType::OWNER)->firstOrFail();
        $owner->givePermissionTo('attributes.show_metadata_private');
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
        $owner->revokePermissionTo('attributes.show_metadata_private');
        $owner->save();

        Permission::query()
            ->where('name', '=', 'attributes.show_metadata_private')
            ->delete();
    }
};
