<?php

use App\Enums\RoleType;
use App\Models\Role;
use Illuminate\Database\Migrations\Migration;

class AuthenticatedRolePermissions extends Migration
{
    public function up(): void
    {
        $authenticated = Role::where('type', RoleType::AUTHENTICATED)->firstOrFail();

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
        ]);

        $authenticated->save();
    }

    public function down(): void
    {
        $authenticated = Role::where('type', RoleType::AUTHENTICATED)->firstOrFail();

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
        ]);

        $authenticated->save();
    }
}
