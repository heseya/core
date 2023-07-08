<?php

use App\Enums\RoleType;
use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Permission::create([
            'name' => 'auth.providers.manage',
            'display_name' => 'Możliwość konfiguracji logowania przez media społecznościowe',
            'description' => 'Uprawnienie pozwalające na dodawanie, edycje i blokowanie możliwości logowania się przez zewnętrznych dostawców jak Google, Apple itp.',
        ]);

        $owner = Role::where('type', '=', RoleType::OWNER)->firstOrFail();
        $owner->givePermissionTo('auth.providers.manage');
        $owner->save();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $owner = Role::where('type', '=', RoleType::OWNER)->firstOrFail();
        $owner->revokePermissionTo('auth.providers.manage');
        $owner->save();

        Permission::where('name', '=', 'auth.providers.manage')->delete();
    }
};
