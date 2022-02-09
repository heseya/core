<?php

use App\Enums\RoleType;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Migrations\Migration;

class AddAuthenticatedRole extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $authenticated = Role::create(['name' => 'Authenticated']);
        $authenticated->type = RoleType::AUTHENTICATED;
        $authenticated->save();

        foreach (User::all() as $user) {
            $user->assignRole($authenticated);
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        $authenticated = Role::where('type', '=', RoleType::AUTHENTICATED)->firstOrFail();
        foreach (User::all() as $user) {
            $user->removeRole($authenticated);
        }

       $authenticated->delete();
    }
}
