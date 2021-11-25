<?php

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Migrations\Migration;

class SeoPermissions extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // SEO
        Permission::create(['name' => 'seo.show', 'display_name' => 'Dostęp do ustawień SEO sklepu']);
        Permission::create(['name' => 'seo.edit', 'display_name' => 'Możliwość edycji ustawień SEO sklepu']);

        $events = Permission::findByName('events.show');
        $events->display_name = 'Dostęp do listy wydarzeń';
        $events->save();

        $owner = Role::findByName('Owner');
        $owner->givePermissionTo([
            'seo.show',
            'seo.edit',
        ]);
        $owner->save();
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        $owner = Role::findByName('Owner');
        $owner->givePermissionTo([
            'seo.show',
            'seo.edit',
        ]);
        $owner->save();

        Permission::findByName('seo.show')->delete();
        Permission::findByName('seo.edit')->delete();
    }
}
