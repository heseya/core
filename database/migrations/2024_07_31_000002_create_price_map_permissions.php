<?php

declare(strict_types=1);

use App\Enums\RoleType;
use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        Permission::create(['name' => 'price-maps.show', 'display_name' => 'Dostęp do listy map cen']);
        Permission::create(['name' => 'price-maps.show_details', 'display_name' => 'Dostęp do szczegółów mapy cen']);
        Permission::create(['name' => 'price-maps.add', 'display_name' => 'Możliwość tworzenia map cen']);
        Permission::create(['name' => 'price-maps.edit', 'display_name' => 'Możliwość edycji map cen']);
        Permission::create(['name' => 'price-maps.remove', 'display_name' => 'Możliwość usuwania map cen']);

        $owner = Role::where('type', RoleType::OWNER->value)->first();
        $owner->givePermissionTo([
            'price-maps.show',
            'price-maps.show_details',
            'price-maps.add',
            'price-maps.edit',
            'price-maps.remove',
        ]);
        $owner->save();
    }

    public function down(): void
    {
        $owner = Role::where('type', RoleType::OWNER->value)->first();

        $owner->revokePermissionTo([
            'price-maps.show',
            'price-maps.show_details',
            'price-maps.add',
            'price-maps.edit',
            'price-maps.remove',
        ]);
        $owner->save();

        Permission::findByName('price-maps.show')->delete();
        Permission::findByName('price-maps.show_details')->delete();
        Permission::findByName('price-maps.add')->delete();
        Permission::findByName('price-maps.edit')->delete();
        Permission::findByName('price-maps.remove')->delete();
    }
};
