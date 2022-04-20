<?php

use App\Enums\RoleType;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Migrations\Migration;

class AddAuthenticatedRole extends Migration
{
    public function up(): void
    {
        $authenticated = Role::create(['name' => 'Authenticated']);
        $authenticated->type = RoleType::AUTHENTICATED;
        $authenticated->save();

        foreach (User::all() as $user) {
            $user->assignRole($authenticated);
        }
    }

    public function down(): void
    {
        $authenticated = Role::where('type', '=', RoleType::AUTHENTICATED);
        foreach (User::all() as $user) {
            $user->removeRole($authenticated);
        }

       $authenticated->delete();
    }
}
