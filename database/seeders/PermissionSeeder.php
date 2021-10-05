<?php

namespace Database\Seeders;

use App\Enums\RoleType;
use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;
use Spatie\Permission\PermissionRegistrar;

class PermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        // Admin
        Permission::updateOrCreate(['name' => 'admin.login']);

        // Analytics
        Permission::updateOrCreate(['name' => 'analytics.payments']);

        // Apps
        Permission::updateOrCreate(['name' => 'apps.show']);
        Permission::updateOrCreate(['name' => 'apps.show_details']);
        Permission::updateOrCreate(['name' => 'apps.install']);
        Permission::updateOrCreate(['name' => 'apps.remove']);

        // Audits
        Permission::updateOrCreate(['name' => 'audits.show']);

        // Auth
        Permission::updateOrCreate(['name' => 'auth.login']);
        Permission::updateOrCreate(['name' => 'auth.register']);
        Permission::updateOrCreate(['name' => 'auth.identity_profile']);
        Permission::updateOrCreate(['name' => 'auth.password_reset']);
        Permission::updateOrCreate(['name' => 'auth.password_change']);
        Permission::updateOrCreate(['name' => 'auth.sessions.show']);
        Permission::updateOrCreate(['name' => 'auth.sessions.revoke']);

        // ProductSets
        Permission::updateOrCreate(['name' => 'product_sets.show']);
        Permission::updateOrCreate(['name' => 'product_sets.show_details']);
        Permission::updateOrCreate(['name' => 'product_sets.show_hidden']);
        Permission::updateOrCreate(['name' => 'product_sets.add']);
        Permission::updateOrCreate(['name' => 'product_sets.edit']);
        Permission::updateOrCreate(['name' => 'product_sets.remove']);

        // Shipping Methods
        Permission::updateOrCreate(['name' => 'countries.show']);
        Permission::updateOrCreate(['name' => 'shipping_methods.show']);
        Permission::updateOrCreate(['name' => 'shipping_methods.show_hidden']);
        Permission::updateOrCreate(['name' => 'shipping_methods.add']);
        Permission::updateOrCreate(['name' => 'shipping_methods.edit']);
        Permission::updateOrCreate(['name' => 'shipping_methods.remove']);

        // Deposits
        Permission::updateOrCreate(['name' => 'deposits.show']);
        Permission::updateOrCreate(['name' => 'deposits.add']);

        // Discounts
        Permission::updateOrCreate(['name' => 'discounts.show']);
        Permission::updateOrCreate(['name' => 'discounts.show_details']);
        Permission::updateOrCreate(['name' => 'discounts.add']);
        Permission::updateOrCreate(['name' => 'discounts.edit']);
        Permission::updateOrCreate(['name' => 'discounts.remove']);

        // Items
        Permission::updateOrCreate(['name' => 'items.show']);
        Permission::updateOrCreate(['name' => 'items.show_details']);
        Permission::updateOrCreate(['name' => 'items.add']);
        Permission::updateOrCreate(['name' => 'items.edit']);
        Permission::updateOrCreate(['name' => 'items.remove']);

        // Schemas
        Permission::updateOrCreate(['name' => 'schemas.remove']);

        // Orders
        Permission::updateOrCreate(['name' => 'cart.verify']);
        Permission::updateOrCreate(['name' => 'orders.show']);
        Permission::updateOrCreate(['name' => 'orders.show_details']);
        Permission::updateOrCreate(['name' => 'orders.show_summary']);
        Permission::updateOrCreate(['name' => 'orders.add']);
        Permission::updateOrCreate(['name' => 'orders.edit']);
        Permission::updateOrCreate(['name' => 'orders.edit.status']);

        // Packages
        Permission::updateOrCreate(['name' => 'packages.show']);
        Permission::updateOrCreate(['name' => 'packages.add']);
        Permission::updateOrCreate(['name' => 'packages.edit']);
        Permission::updateOrCreate(['name' => 'packages.remove']);

        // Pages
        Permission::updateOrCreate(['name' => 'pages.show']);
        Permission::updateOrCreate(['name' => 'pages.show_details']);
        Permission::updateOrCreate(['name' => 'pages.show_hidden']);
        Permission::updateOrCreate(['name' => 'pages.add']);
        Permission::updateOrCreate(['name' => 'pages.edit']);
        Permission::updateOrCreate(['name' => 'pages.remove']);

        // Payments
        Permission::updateOrCreate(['name' => 'payments.add']);
        Permission::updateOrCreate(['name' => 'payments.edit']);
        Permission::updateOrCreate(['name' => 'payments.offline']);

        // Payment Methods
        Permission::updateOrCreate(['name' => 'payment_methods.show']);
        Permission::updateOrCreate(['name' => 'payment_methods.show_hidden']);
        Permission::updateOrCreate(['name' => 'payment_methods.add']);
        Permission::updateOrCreate(['name' => 'payment_methods.edit']);
        Permission::updateOrCreate(['name' => 'payment_methods.remove']);

        // Product
        Permission::updateOrCreate(['name' => 'products.show']);
        Permission::updateOrCreate(['name' => 'products.show_details']);
        Permission::updateOrCreate(['name' => 'products.show_hidden']);
        Permission::updateOrCreate(['name' => 'products.add']);
        Permission::updateOrCreate(['name' => 'products.edit']);
        Permission::updateOrCreate(['name' => 'products.remove']);

        // Settings
        Permission::updateOrCreate(['name' => 'settings.show']);
        Permission::updateOrCreate(['name' => 'settings.show_details']);
        Permission::updateOrCreate(['name' => 'settings.show_hidden']);
        Permission::updateOrCreate(['name' => 'settings.add']);
        Permission::updateOrCreate(['name' => 'settings.edit']);
        Permission::updateOrCreate(['name' => 'settings.remove']);

        // Statuses
        Permission::updateOrCreate(['name' => 'statuses.show']);
        Permission::updateOrCreate(['name' => 'statuses.add']);
        Permission::updateOrCreate(['name' => 'statuses.edit']);
        Permission::updateOrCreate(['name' => 'statuses.remove']);

        // Tags
        Permission::updateOrCreate(['name' => 'tags.show']);
        Permission::updateOrCreate(['name' => 'tags.add']);
        Permission::updateOrCreate(['name' => 'tags.edit']);
        Permission::updateOrCreate(['name' => 'tags.remove']);

        // Users
        Permission::updateOrCreate(['name' => 'users.show']);
        Permission::updateOrCreate(['name' => 'users.show_details']);
        Permission::updateOrCreate(['name' => 'users.add']);
        Permission::updateOrCreate(['name' => 'users.edit']);
        Permission::updateOrCreate(['name' => 'users.remove']);

        // Roles
        Permission::updateOrCreate(['name' => 'roles.show']);
        Permission::updateOrCreate(['name' => 'roles.show_details']);
        Permission::updateOrCreate(['name' => 'roles.add']);
        Permission::updateOrCreate(['name' => 'roles.edit']);
        Permission::updateOrCreate(['name' => 'roles.remove']);

        // WebHooks
        Permission::updateOrCreate(['name' => 'webhooks.show']);
        Permission::updateOrCreate(['name' => 'webhooks.show_details']);
        Permission::updateOrCreate(['name' => 'webhooks.add']);
        Permission::updateOrCreate(['name' => 'webhooks.edit']);
        Permission::updateOrCreate(['name' => 'webhooks.remove']);

        $owner = Role::updateOrCreate(['name' => 'Owner'])
            ->givePermissionTo(Permission::all());
        $owner->type = RoleType::OWNER;
        $owner->save();

        $unauthenticated = Role::updateOrCreate(['name' => 'Unauthenticated'])
            ->givePermissionTo([
                'auth.login',
                'auth.register',
                'auth.password_reset',
                'auth.password_change',
                'product_sets.show',
                'product_sets.show_details',
                'countries.show',
                'shipping_methods.show',
                'discounts.show_details',
                'cart.verify',
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
        $unauthenticated->type = RoleType::UNAUTHENTICATED;
        $unauthenticated->save();
    }
}
