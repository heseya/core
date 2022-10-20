<?php

use App\Enums\RoleType;
use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Migrations\Migration;

class AddSalesShowDetailsPermission extends Migration
{
    public function up(): void
    {
        Permission::create(['name' => 'sales.show_details', 'display_name' => 'Dostęp do szczegółów promocji']);

        $owner = Role::where('type', RoleType::OWNER)->first();
        $owner->givePermissionTo([
            'sales.show_details',
        ]);
        $owner->save();
    }

    public function down(): void
    {
        $owner = Role::where('type', RoleType::OWNER)->first();
        $owner->revokePermissionTo([
            'sales.show_details',
        ]);
        $owner->save();

        Permission::findByName('sales.show_details')->delete();
    }
}
