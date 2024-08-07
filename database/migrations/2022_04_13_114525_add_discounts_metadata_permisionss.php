<?php

use App\Enums\RoleType;
use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration {
    public function up(): void
    {
        Permission::findByName('discounts.show_metadata_private')->update([
            'name' => 'sales.show_metadata_private',
            'Możliwość wyświetlania prywatnych metadanych promocji',
        ]);

        Permission::create(['name' => 'coupons.show_metadata_private', 'display_name' => 'Możliwość wyświetlania prywatnych metadanych kuponów']);

        $owner = Role::where('type', '=', RoleType::OWNER)->firstOrFail();
        $owner->givePermissionTo('coupons.show_metadata_private');
        $owner->save();
    }

    public function down(): void
    {
        Permission::findByName('sales.show_metadata_private')->update([
            'name' => 'discounts.show_metadata_private',
            'Możliwość wyświetlania prywatnych metadanych kuponów',
        ]);

        $owner = Role::where('type', '=', RoleType::OWNER)->firstOrFail();
        $owner->revokePermissionTo('coupons.show_metadata_private');
        $owner->save();

        Permission::where('name', '=', 'coupons.show_metadata_private')->delete();
    }
};
