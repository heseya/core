<?php

use App\Enums\RoleType;
use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration {
    public function up()
    {
        Permission::create(['name' => 'schemas.show_hidden', 'display_name' => 'Dostęp do ukrytych schematów']);
        Permission::create(['name' => 'statuses.show_hidden', 'display_name' => 'Dostęp do ukrytych statusów zamówień']);
        Permission::create(['name' => 'options.show_hidden', 'display_name' => 'Dostęp do ukrytych opcji schematów']);

        $owner = Role::where('type', RoleType::OWNER)->first();
        $owner->givePermissionTo([
            'schemas.show_hidden',
            'statuses.show_hidden',
            'options.show_hidden',
        ]);
        $owner->save();
    }

    public function down()
    {
        $owner = Role::where('type', RoleType::OWNER)->first();
        $owner->revokePermissionsTo([
            'schemas.show_hidden',
            'statuses.show_hidden',
            'options.show_hidden',
        ]);
        $owner->save();

        Permission::where('name', 'schemas.show_hidden')->first()->delete();
        Permission::where('name', 'statuses.show_hidden')->first()->delete();
        Permission::where('name', 'options.show_hidden')->first()->delete();
    }
};
