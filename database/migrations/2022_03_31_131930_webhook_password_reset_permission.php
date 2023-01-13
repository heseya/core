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
        Permission::create([
            'name' => 'webhooks.password',
            'display_name' => 'Możliwość zarządzania webhookami przywracania hasła',
            'description' => 'Pozwala na tworzenie i zarządzanie webhookami odpowiadającymi za przesyłanie linków do przywracania hasła użytkownikom',
        ]);

        $owner = Role::where('type', RoleType::OWNER)->first();
        $owner->givePermissionTo([
            'webhooks.password',
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
            'webhooks.password',
        ]);
        $owner->save();

        Permission::findByName('webhooks.password')->delete();
    }
};
