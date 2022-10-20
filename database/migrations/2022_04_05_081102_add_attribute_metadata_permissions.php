<?php

use App\Enums\RoleType;
use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Migrations\Migration;

return new class() extends Migration {
    public function up(): void
    {
        Permission::create(['name' => 'attributes.show_metadata_private', 'display_name' => 'Możliwość wyświetlania prywatnych metadanych atrybutów oraz opcji atrybutów']);

        $owner = Role::query()->where('type', '=', RoleType::OWNER)->firstOrFail();
        $owner->givePermissionTo('attributes.show_metadata_private');
        $owner->save();
    }

    public function down(): void
    {
        $owner = Role::query()->where('type', '=', RoleType::OWNER)->firstOrFail();
        $owner->revokePermissionTo('attributes.show_metadata_private');
        $owner->save();

        Permission::query()
            ->where('name', '=', 'attributes.show_metadata_private')
            ->delete();
    }
};
