<?php

use App\Enums\RoleType;
use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration {
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Permission::create([
            'name' => 'webhooks.tfa',
            'display_name' => 'Możliwość zarządzania webhookami weryfikacji dwuetapowej',
            'description' => 'Pozwala na tworzenie i zarządzanie webhookami odpowiadającymi za przesyłanie kodów Weryfikacji dwuetapowej',
        ]);

        $owner = Role::where('type', RoleType::OWNER)->first();
        $owner->givePermissionTo([
            'webhooks.tfa',
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
            'webhooks.tfa',
        ]);
        $owner->save();

        Permission::findByName('webhooks.tfa')->delete();
    }
};
