<?php

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Migrations\Migration;

class WebhookPermissions extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // WebHooks
        Permission::create(['name' => 'webhooks.show', 'display_name' => 'Dostęp do listy webhooków']);
        Permission::create(['name' => 'webhooks.show_details', 'display_name' => 'Dostęp do szczegółów webhooków']);
        Permission::create(['name' => 'webhooks.add', 'display_name' => 'Możliwość tworzenia webhooków']);
        Permission::create(['name' => 'webhooks.edit', 'display_name' => 'Możliwość edycji webhooków']);
        Permission::create(['name' => 'webhooks.remove', 'display_name' => 'Możliwość usuwania webhooków']);

        // Events
        Permission::create(['name' => 'events.show', 'display_name' => 'Dostęp do listy eventów']);

        $owner = Role::findByName('Owner');
        $owner->givePermissionTo([
            'webhooks.show',
            'webhooks.show_details',
            'webhooks.add',
            'webhooks.edit',
            'webhooks.remove',
            'events.show',
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
        $owner->revokePermissionTo([
            'webhooks.show',
            'webhooks.show_details',
            'webhooks.add',
            'webhooks.edit',
            'webhooks.remove',
            'events.show',
        ]);
        $owner->save();

        Permission::findByName('webhooks.show')->delete();
        Permission::findByName('webhooks.show_details')->delete();
        Permission::findByName('webhooks.add')->delete();
        Permission::findByName('webhooks.edit')->delete();
        Permission::findByName('webhooks.remove')->delete();
        Permission::findByName('events.show')->delete();
    }
}
