<?php

use App\Enums\RoleType;
use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddSalesShowDetailsPermission extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Permission::create(['name' => 'sales.show_details', 'display_name' => 'Dostęp do szczegółów promocji']);

        $owner = Role::where('type', RoleType::OWNER)->first();
        $owner->givePermissionTo([
            'sales.show_details',
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
        $owner = Role::where('type', RoleType::OWNER)->first();
        $owner->revokePermissionTo([
            'sales.show_details',
        ]);
        $owner->save();

        Permission::findByName('sales.show_details')->delete();
    }
}
