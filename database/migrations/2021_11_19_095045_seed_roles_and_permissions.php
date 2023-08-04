<?php

use App\Enums\RoleType;
use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Migrations\Migration;

class SeedRolesAndPermissions extends Migration
{
    public function up(): void
    {
        // Admin
        Permission::create(['name' => 'admin.login', 'display_name' => 'Możliwość logowania do panelu administracyjnego']);

        // Analytics
        Permission::create(['name' => 'analytics.payments', 'display_name' => 'Dostęp do analityki płatności']);

        // Apps
        Permission::create(['name' => 'apps.show', 'display_name' => 'Dostęp do listy aplikacji']);
        Permission::create(['name' => 'apps.show_details', 'display_name' => 'Dostęp do szczegółów aplikacji']);
        Permission::create(['name' => 'apps.install', 'display_name' => 'Możliwość instalowania aplikacji']);
        Permission::create(['name' => 'apps.remove', 'display_name' => 'Możliwość usuwania aplikacji']);

        // Audits
        Permission::create([
            'name' => 'audits.show',
            'display_name' => 'Dostęp do listy zmian',
            'description' => 'Wyświetlanie i pobieranie historii zmian np. zamówień',
        ]);

        // Auth
        Permission::create(['name' => 'auth.login', 'display_name' => 'Możliwość logowania użytkownika']);
        Permission::create(['name' => 'auth.register', 'display_name' => 'Możliwość rejestracji użytkownika']);
        Permission::create([
            'name' => 'auth.check_identity',
            'display_name' => 'Możliwość identyfikacji użytkownika',
            'description' => 'Uprawnienie dedykowane aplikacjom, umożliwia im weryfikowanie użytkowników',
        ]);
        Permission::create(['name' => 'auth.password_reset', 'display_name' => 'Możliwość resetowania hasła użytkownika']);
        Permission::create(['name' => 'auth.password_change', 'display_name' => 'Możliwość zmiany hasła użytkownika']);
        Permission::create(['name' => 'auth.sessions.show', 'display_name' => 'Dostęp do listy sesji zalogowanego użytkownika']);
        Permission::create(['name' => 'auth.sessions.revoke', 'display_name' => 'Możliwość blokowania sesji użytkownika']);

        // ProductSets
        Permission::create(['name' => 'product_sets.show', 'display_name' => 'Dostęp do listy kolekcji']);
        Permission::create(['name' => 'product_sets.show_details', 'display_name' => 'Dostęp do szczegółów kolekcji']);
        Permission::create(['name' => 'product_sets.show_hidden', 'display_name' => 'Dostęp do ukrytych kolekcji']);
        Permission::create(['name' => 'product_sets.add', 'display_name' => 'Możliwość tworzenia kolekcji']);
        Permission::create(['name' => 'product_sets.edit', 'display_name' => 'Możliwość edycji kolekcji']);
        Permission::create(['name' => 'product_sets.remove', 'display_name' => 'Możliwość usuwania kolekcji']);

        // Shipping Methods
        Permission::create(['name' => 'countries.show', 'display_name' => 'Dostęp do listy krajów']);
        Permission::create(['name' => 'shipping_methods.show', 'display_name' => 'Dostęp do listy metod dostawy']);
        Permission::create(['name' => 'shipping_methods.show_hidden', 'display_name' => 'Dostęp do ukrytych metod dostawy']);
        Permission::create(['name' => 'shipping_methods.add', 'display_name' => 'Możliwość tworzenia metod dostawy']);
        Permission::create(['name' => 'shipping_methods.edit', 'display_name' => 'Możliwość edycji metod dostawy']);
        Permission::create(['name' => 'shipping_methods.remove', 'display_name' => 'Możliwość usuwania metod dostawy']);

        // Deposits
        Permission::create([
            'name' => 'deposits.show',
            'display_name' => 'Dostęp do listy depozytów',
            'description' => 'Lista zmian ilości w przedmiotach magazynowych',
        ]);
        Permission::create([
            'name' => 'deposits.add',
            'display_name' => 'Możliwość tworzenia wpisu depozytowego',
            'description' => 'Zmiana ilości przedmiotów w magazynie',
        ]);

        // Discounts
        Permission::create(['name' => 'discounts.show', 'display_name' => 'Dostęp do listy kodów rabatowych']);
        Permission::create([
            'name' => 'discounts.show_details',
            'display_name' => 'Dostęp do szczegółów kodów rabatowych',
            'description' => 'Sprawdzenie, czy zniżka o danym kodzie istnieje i pobranie jej szczegółów',
        ]);
        Permission::create(['name' => 'discounts.add', 'display_name' => 'Możliwość tworzenia kodów rabatowych']);
        Permission::create(['name' => 'discounts.edit', 'display_name' => 'Możliwość edycji kodów rabatowych']);
        Permission::create(['name' => 'discounts.remove', 'display_name' => 'Możliwość usuwania kodów rabatowych']);

        // Items
        Permission::create(['name' => 'items.show', 'display_name' => 'Dostęp do listy przedmiotów magazynowych']);
        Permission::create(['name' => 'items.show_details', 'display_name' => 'Dostęp do szczegółów przedmiotów magazynowych']);
        Permission::create(['name' => 'items.add', 'display_name' => 'Możliwość tworzenia przedmiotów magazynowych']);
        Permission::create(['name' => 'items.edit', 'display_name' => 'Możliwość edycji przedmiotów magazynowych']);
        Permission::create(['name' => 'items.remove', 'display_name' => 'Możliwość usuwania przedmiotów magazynowych']);

        // Schemas
        Permission::create(['name' => 'schemas.remove', 'display_name' => 'Możliwość usuwania globalnych schematów']);

        // Orders
        Permission::create([
            'name' => 'cart.verify',
            'display_name' => 'Możliwość weryfikacji zawartości koszyka',
            'description' => 'Możliwość sprawdzenia, czy koszyk użytkownika może zostać zakupiony (Czy wszystkie jego elementy są dostępne itp.)',
        ]);
        Permission::create(['name' => 'orders.show', 'display_name' => 'Dostęp do listy zamówień']);
        Permission::create(['name' => 'orders.show_details', 'display_name' => 'Dostęp do szczegółów zamówień']);
        Permission::create(['name' => 'orders.show_summary', 'display_name' => 'Dostęp do podsumowania zamówienia']);
        Permission::create(['name' => 'orders.add', 'display_name' => 'Możliwość tworzenia zamówienia']);
        Permission::create(['name' => 'orders.edit', 'display_name' => 'Możliwość edycji zamówienia']);
        Permission::create(['name' => 'orders.edit.status', 'display_name' => 'Możliwość edycji statusu zamówień']);

        // Packages
        Permission::create(['name' => 'packages.show', 'display_name' => 'Dostęp do listy szablonów przesyłek']);
        Permission::create(['name' => 'packages.add', 'display_name' => 'Możliwość tworzenia szablonów przesyłek']);
        Permission::create(['name' => 'packages.edit', 'display_name' => 'Możliwość usuwania szablonów przesyłek']);
        Permission::create(['name' => 'packages.remove', 'display_name' => 'Możliwość edycji szablonów przesyłek']);

        // Pages
        Permission::create(['name' => 'pages.show', 'display_name' => 'Dostęp do listy stron']);
        Permission::create(['name' => 'pages.show_details', 'display_name' => 'Dostęp do szczegółów stron']);
        Permission::create(['name' => 'pages.show_hidden', 'display_name' => 'Dostęp do ukrytych stron']);
        Permission::create(['name' => 'pages.add', 'display_name' => 'Możliwość tworzenia stron']);
        Permission::create(['name' => 'pages.edit', 'display_name' => 'Możliwość edycji stron']);
        Permission::create(['name' => 'pages.remove', 'display_name' => 'Możliwość usuwania stron']);

        // Payments
        Permission::create(['name' => 'payments.add', 'display_name' => 'Możliwość tworzenia transakcji']);
        Permission::create(['name' => 'payments.edit', 'display_name' => 'Możliwość edycji transakcji']);
        Permission::create([
            'name' => 'payments.offline',
            'display_name' => 'Możliwość tworzenia transakcji offline',
            'description' => 'Ręczne opłacanie zamówień z panelu (np. gotówką)',
        ]);

        // Payment Methods
        Permission::create(['name' => 'payment_methods.show', 'display_name' => 'Dostęp do listy metod płatności']);
        Permission::create(['name' => 'payment_methods.show_hidden', 'display_name' => 'Dostęp do ukrytych metod płatności']);
        Permission::create(['name' => 'payment_methods.add', 'display_name' => 'Możliwość tworzenia metod płatności']);
        Permission::create(['name' => 'payment_methods.edit', 'display_name' => 'Możliwość edycji metod płatności']);
        Permission::create(['name' => 'payment_methods.remove', 'display_name' => 'Możliwość usuwania metod płatności']);

        // Product
        Permission::create(['name' => 'products.show', 'display_name' => 'Dostęp do listy produktów']);
        Permission::create(['name' => 'products.show_details', 'display_name' => 'Dostęp do szczegółów produktów']);
        Permission::create(['name' => 'products.show_hidden', 'display_name' => 'Dostęp do ukrytych produktów']);
        Permission::create(['name' => 'products.add', 'display_name' => 'Możliwość tworzenia produktów']);
        Permission::create(['name' => 'products.edit', 'display_name' => 'Możliwość edycji produktów']);
        Permission::create(['name' => 'products.remove', 'display_name' => 'Możliwość usuwania produktów']);

        // Settings
        Permission::create(['name' => 'settings.show', 'display_name' => 'Dostęp do listy ustawień zaawansowanych']);
        Permission::create(['name' => 'settings.show_details', 'display_name' => 'Dostęp do szczegółów ustawień zaawansowanych']);
        Permission::create(['name' => 'settings.show_hidden', 'display_name' => 'Dostęp do ukrytych ustawień zaawansowanych']);
        Permission::create(['name' => 'settings.add', 'display_name' => 'Możliwość tworzenia ustawień zaawansowanych']);
        Permission::create(['name' => 'settings.edit', 'display_name' => 'Możliwość edycji ustawień zaawansowanych']);
        Permission::create(['name' => 'settings.remove', 'display_name' => 'Możliwość usuwania ustawień zaawansowanych']);

        // Statuses
        Permission::create(['name' => 'statuses.show', 'display_name' => 'Dostęp do listy statusów zamówień']);
        Permission::create(['name' => 'statuses.add', 'display_name' => 'Możliwość tworzenia statusów zamówień']);
        Permission::create(['name' => 'statuses.edit', 'display_name' => 'Możliwość edycji statusów zamówień']);
        Permission::create(['name' => 'statuses.remove', 'display_name' => 'Możliwość usuwania statusów zamówień']);

        // Tags
        Permission::create(['name' => 'tags.show', 'display_name' => 'Dostęp do listy tagów']);
        Permission::create(['name' => 'tags.add', 'display_name' => 'Możliwość tworzenia tagów']);
        Permission::create(['name' => 'tags.edit', 'display_name' => 'Możliwość edycji tagów']);
        Permission::create(['name' => 'tags.remove', 'display_name' => 'Możliwość usuwania tagów']);

        // Users
        Permission::create(['name' => 'users.show', 'display_name' => 'Dostęp do listy użytkowników']);
        Permission::create(['name' => 'users.show_details', 'display_name' => 'Dostęp do szczegółów użytkowników']);
        Permission::create(['name' => 'users.add', 'display_name' => 'Możliwość tworzenia użytkowników']);
        Permission::create(['name' => 'users.edit', 'display_name' => 'Możliwość edycji użytkowników']);
        Permission::create(['name' => 'users.remove', 'display_name' => 'Możliwość usuwania użytkowników']);

        // Roles
        Permission::create(['name' => 'roles.show', 'display_name' => 'Dostęp do listy ról użytkowników']);
        Permission::create(['name' => 'roles.show_details', 'display_name' => 'Dostęp do szczegółów ról użytkowników']);
        Permission::create(['name' => 'roles.add', 'display_name' => 'Możliwość tworzenia ról użytkowników']);
        Permission::create(['name' => 'roles.edit', 'display_name' => 'Możliwość edycji ról użytkowników']);
        Permission::create(['name' => 'roles.remove', 'display_name' => 'Możliwość usuwania ról użytkowników']);

        $owner = Role::create(['name' => 'Owner'])
            ->givePermissionTo(Permission::all());
        $owner->type = RoleType::OWNER;
        $owner->save();

        $unauthenticated = Role::create(['name' => 'Unauthenticated'])
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
                'payments.edit',
                'payments.add',
                'payment_methods.show',
                'products.show',
                'products.show_details',
                'settings.show',
                'tags.show',
            ]);
        $unauthenticated->type = RoleType::UNAUTHENTICATED;
        $unauthenticated->save();
    }

    public function down(): void
    {
        Permission::truncate();
        Role::truncate();
    }
}
