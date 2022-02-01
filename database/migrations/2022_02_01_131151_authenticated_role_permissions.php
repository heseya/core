<?php

use App\Models\Role;
use Illuminate\Database\Migrations\Migration;

class AuthenticatedRolePermissions extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $authenticated = Role::findByName('Authenticated');

        $authenticated->givePermissionTo([
            'auth.login',
            'auth.check_identity',
            'auth.password_reset',
            'auth.password_change',
            'auth.sessions.show',
            'auth.sessions.revoke',
            'product_sets.show',
            'product_sets.show_details',
            'countries.show',
            'shipping_methods.show',
            'discounts.show_details',
            'cart.verify',
            'orders.show_own',
            'orders.add',
            'orders.show_summary',
            'pages.show',
            'pages.show_details',
            'payment_methods.show',
            'products.show',
            'products.show_details',
            'settings.show',
            'tags.show',
            'seo.show',
        ]);

        $authenticated->save();
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        $authenticated = Role::findByName('Authenticated');

        $authenticated->revokePermissionTo([
            'auth.login',
            'auth.check_identity',
            'auth.password_reset',
            'auth.password_change',
            'auth.sessions.show',
            'auth.sessions.revoke',
            'product_sets.show',
            'product_sets.show_details',
            'countries.show',
            'shipping_methods.show',
            'discounts.show_details',
            'cart.verify',
            'orders.show_own',
            'orders.add',
            'orders.show_summary',
            'pages.show',
            'pages.show_details',
            'payment_methods.show',
            'products.show',
            'products.show_details',
            'settings.show',
            'tags.show',
            'seo.show',
        ]);

        $authenticated->save();
    }
}
