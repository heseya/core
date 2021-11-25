<?php

use App\Enums\RoleType;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Migrations\Migration;

class AddOwnerToUsers extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $owner = Role::where('type', RoleType::OWNER)->firstOrFail();

        foreach (User::all() as $user) {
            $user->assignRole($owner);
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        foreach (User::all() as $user) {
            $user->syncRoles([]);
        }
    }
}
