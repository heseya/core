<?php

use App\Enums\RoleType;
use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Permission::create([
            'name' => 'attributes.show_hidden',
            'display_name' => 'Dostęp do ukrytych atrybutów',
        ]);

        Role::query()
            ->where('type', RoleType::OWNER->value)
            ->firstOrFail()
            ->givePermissionTo('attributes.show_hidden');
    }

    public function down(): void
    {
        Role::query()
            ->where('type', RoleType::OWNER->value)
            ->firstOrFail()
            ->revokePermissionTo('attributes.show_hidden');

        Permission::query()
            ->where('name', 'attributes.show_hidden')
            ->delete();
    }
};
