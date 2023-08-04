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
        /** @var Role $unauthenticated */
        $unauthenticated = Role::query()->where('type', RoleType::UNAUTHENTICATED->value)->firstOrFail();
        $unauthenticated->givePermissionTo('attributes.show');
        $unauthenticated->save();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        /** @var Role $unauthenticated */
        $unauthenticated = Role::query()->where('type', RoleType::UNAUTHENTICATED->value)->firstOrFail();
        $unauthenticated->revokePermissionTo('attributes.show');
        $unauthenticated->save();
    }
};
