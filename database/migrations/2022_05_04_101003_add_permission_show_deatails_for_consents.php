<?php

use App\Enums\RoleType;
use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration {
    public function up(): void
    {
        Permission::create([
            'name' => 'consents.show_details',
            'display_name' => 'Możliwość wyświetlania szczegółów zgody',
        ]);

        Role::where('type', RoleType::OWNER)
            ->firstOrFail()
            ->givePermissionTo('consents.show_details');
    }

    public function down(): void
    {
        Role::where('type', RoleType::OWNER)
            ->firstOrFail()
            ->revokePermissionTo('consents.show_details');

        Permission::query()
            ->where('name', '=', 'consents.show_details')
            ->delete();
    }
};
