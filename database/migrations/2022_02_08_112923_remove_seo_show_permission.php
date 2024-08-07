<?php

use App\Enums\RoleType;
use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Migrations\Migration;

class RemoveSeoShowPermission extends Migration
{
    public function up(): void
    {
        $seo_show = Permission::where('name', 'seo.show')->first();

        foreach (Role::all() as $role) {
            $role->revokePermissionTo($seo_show);
        }

        $seo_show->delete();
    }

    public function down(): void
    {
        Permission::create(['name' => 'seo.show', 'display_name' => 'Dostęp do ustawień SEO sklepu']);

        $owner = Role::where('type', RoleType::OWNER)->first();
        $owner->givePermissionTo([
            'seo.show',
        ]);
        $owner->save();

        $unauthenticated = Role::where('type', RoleType::UNAUTHENTICATED)->first();
        $unauthenticated->givePermissionTo([
            'seo.show',
        ]);
        $unauthenticated->save();
    }
}
