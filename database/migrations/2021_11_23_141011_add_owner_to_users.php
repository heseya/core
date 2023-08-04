<?php

use App\Enums\RoleType;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Migrations\Migration;

class AddOwnerToUsers extends Migration
{
    public function up(): void
    {
        $owner = Role::where('type', RoleType::OWNER->value)->firstOrFail();

        foreach (User::all() as $user) {
            $user->assignRole($owner);
        }
    }

    public function down(): void
    {
        foreach (User::all() as $user) {
            $user->syncRoles([]);
        }
    }
}
