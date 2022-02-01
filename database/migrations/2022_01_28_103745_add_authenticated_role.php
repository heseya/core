<?php

use App\Enums\RoleType;
use App\Models\Role;
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
        $logged_user = Role::create(['name' => 'Authenticated']);
        $logged_user->type = RoleType::AUTHENTICATED;
        $logged_user->save();
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Role::where('type', '=', RoleType::AUTHENTICATED)->delete();
    }
}
