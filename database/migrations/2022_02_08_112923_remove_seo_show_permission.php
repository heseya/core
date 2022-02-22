<?php

use App\Enums\RoleType;
use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Migrations\Migration;

class RemoveSeoShowPermission extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $seo_show = Permission::where('name', 'seo.show')->first();

        foreach (Role::all() as $role) {
            $role->revokePermissionTo($seo_show);
        }

        $seo_show->delete();
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Permission::create(['name' => 'seo.show', 'display_name' => 'Dostęp do ustawień SEO sklepu']);

        $owner = Role::find('type', RoleType::OWNER);
        $owner->givePermissionTo([
            'seo.show',
        ]);
        $owner->save();

        $unauthenticated = Role::find('type', RoleType::UNAUTHENTICATED);
        $unauthenticated->givePermissionTo([
            'seo.show',
        ]);
        $unauthenticated->save();
    }
}