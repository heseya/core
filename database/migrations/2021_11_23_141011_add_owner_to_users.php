<?php

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
        $owner = Role::where('name', 'Owner')->firstOrFail();

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
