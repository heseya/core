<?php

use App\Enums\RoleType;
use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Permission::create(['name' => 'app_widgets.show', 'display_name' => 'Dostęp do listy widgetów']);
        Permission::create(['name' => 'app_widgets.add', 'display_name' => 'Możliwość instalacji widgetów']);
        Permission::create(['name' => 'app_widgets.edit', 'display_name' => 'Możliwość edycji widgetów']);
        Permission::create(['name' => 'app_widgets.remove', 'display_name' => 'Możliwość usuwania widgetów']);

        $role = Role::where('type', RoleType::OWNER->value)->first();
        $role?->givePermissionTo(
            'app_widgets.show',
            'app_widgets.add',
            'app_widgets.edit',
            'app_widgets.remove',
        );
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Permission::query()
            ->whereIn('name', [
                'app_widgets.show',
                'app_widgets.add',
                'app_widgets.edit',
                'app_widgets.remove',
            ])
            ->delete();
    }
};
