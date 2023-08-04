<?php

use App\Enums\RoleType;
use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Migrations\Migration;

class ModifyAndAddCouponsAndSalesPermission extends Migration
{
    public function up(): void
    {
        Permission::create(['name' => 'sales.show', 'display_name' => 'Dostęp do listy promocji']);
        Permission::create(['name' => 'sales.add', 'display_name' => 'Możliwość tworzenia promocji']);
        Permission::create(['name' => 'sales.edit', 'display_name' => 'Możliwość edycji promocji']);
        Permission::create(['name' => 'sales.remove', 'display_name' => 'Możliwość usuwania promocji']);

        Permission::findByName('discounts.show')->update(['name' => 'coupons.show']);
        Permission::findByName('discounts.show_details')->update(['name' => 'coupons.show_details']);
        Permission::findByName('discounts.add')->update(['name' => 'coupons.add']);
        Permission::findByName('discounts.edit')->update(['name' => 'coupons.edit']);
        Permission::findByName('discounts.remove')->update(['name' => 'coupons.remove']);

        $owner = Role::where('type', RoleType::OWNER->value)->first();
        $owner->givePermissionTo([
            'sales.show',
            'sales.add',
            'sales.edit',
            'sales.remove',
        ]);
        $owner->save();
    }

    public function down(): void
    {
        $owner = Role::where('type', RoleType::OWNER->value)->first();
        $owner->revokePermissionTo([
            'sales.show',
            'sales.add',
            'sales.edit',
            'sales.remove',
        ]);
        $owner->save();

        Permission::findByName('sales.show')->delete();
        Permission::findByName('sales.add')->delete();
        Permission::findByName('sales.edit')->delete();
        Permission::findByName('sales.remove')->delete();

        Permission::findByName('coupons.show')->update(['name' => 'discounts.show']);
        Permission::findByName('coupons.show_details')->update(['name' => 'discounts.show_details']);
        Permission::findByName('coupons.add')->update(['name' => 'discounts.add']);
        Permission::findByName('coupons.edit')->update(['name' => 'discounts.edit']);
        Permission::findByName('coupons.remove')->update(['name' => 'discounts.remove']);
    }
}
