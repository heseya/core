<?php

use App\Enums\RoleType;
use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Migrations\Migration;

class AttributePermissions extends Migration
{
    public function up(): void
    {
        Permission::create(['name' => 'attributes.show', 'display_name' => 'Dostęp do listy cech']);
        Permission::create(['name' => 'attributes.add', 'display_name' => 'Możliwość tworzenia cech']);
        Permission::create([
            'name' => 'attributes.edit',
            'display_name' => 'Możliwość edycji cech oraz modyfikacji ich opcji',
            'description' => 'Pozwala również na dodawanie nowych i usuwanie opcji cech',
        ]);
        Permission::create(['name' => 'attributes.remove', 'display_name' => 'Możliwość usuwania cech']);

        $owner = Role::query()->where('type', '=', RoleType::OWNER)->firstOrFail();
        $owner->givePermissionTo([
            'attributes.show',
            'attributes.add',
            'attributes.edit',
            'attributes.remove',
        ]);
        $owner->save();

        $authenticated = Role::query()->where('type', '=', RoleType::AUTHENTICATED)->firstOrFail();
        $authenticated->givePermissionTo([
            'attributes.show',
        ]);
        $authenticated->save();
    }

    public function down(): void
    {
        $owner = Role::query()->where('type', '=', RoleType::OWNER)->firstOrFail();
        $owner->revokePermissionTo([
            'attributes.show',
            'attributes.add',
            'attributes.edit',
            'attributes.remove',
        ]);
        $owner->save();

        $authenticated = Role::query()->where('type', '=', RoleType::AUTHENTICATED)->firstOrFail();
        $authenticated->revokePermissionTo([
            'attributes.show',
        ]);
        $authenticated->save();

        Permission::findByName('attributes.show')->delete();
        Permission::findByName('attributes.add')->delete();
        Permission::findByName('attributes.edit')->delete();
        Permission::findByName('attributes.remove')->delete();
    }
}
