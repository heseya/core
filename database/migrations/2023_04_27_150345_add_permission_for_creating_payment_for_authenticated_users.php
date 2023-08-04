<?php

use App\Enums\RoleType;
use App\Models\Role;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        /** @var Role $authenticated */
        $authenticated = Role::query()->where('type', RoleType::AUTHENTICATED->value)->firstOrFail();
        $authenticated->givePermissionTo('payments.add');
        $authenticated->save();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        /** @var Role $authenticated */
        $authenticated = Role::query()->where('type', RoleType::AUTHENTICATED->value)->firstOrFail();
        $authenticated->revokePermissionTo('payments.add');
        $authenticated->save();
    }
};
