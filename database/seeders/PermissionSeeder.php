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
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        // Admin
        Permission::updateOrCreate(['name' => 'admin.login'], ['display_name' => 'Możliwość logowania do panelu administracyjnego']);

        // Analytics
        Permission::updateOrCreate(['name' => 'analytics.payments'], ['display_name' => 'Dostęp do analityki płatności']);

        // Apps
        Permission::updateOrCreate(['name' => 'apps.show'], ['display_name' => 'Dostęp do listy aplikacji']);
        Permission::updateOrCreate(['name' => 'apps.show_details'], ['display_name' => 'Dostęp do szczegółów aplikacji']);
        Permission::updateOrCreate(['name' => 'apps.install'], ['display_name' => 'Możliwość instalowania aplikacji']);
        Permission::updateOrCreate(['name' => 'apps.remove'], ['display_name' => 'Możliwość usuwania aplikacji']);

        // Audits
        Permission::updateOrCreate(['name' => 'audits.show'], [
            'display_name' => 'Dostęp do listy zmian',
            'description' => 'Wyświetlanie i pobieranie historii zmian np. zamówień',
        ]);

        // Auth
        Permission::updateOrCreate(['name' => 'auth.login'], ['display_name' => 'Możliwość logowania użytkownika']);
        Permission::updateOrCreate(['name' => 'auth.register'], ['display_name' => 'Możliwość rejestracji użytkownika']);
        Permission::updateOrCreate(['name' => 'auth.identity_profile'], [
            'display_name' => 'Możliwość identyfikacji użytkownika',
            'description' => 'Uprawnienie dedykowane aplikacjom, umożliwia im weryfikowanie użytkowników',
        ]);
        Permission::updateOrCreate(['name' => 'auth.password_reset'], ['display_name' => 'Możliwość resetowania hasła użytkownika']);
        Permission::updateOrCreate(['name' => 'auth.password_change'], ['display_name' => 'Możliwość zmiany hasła użytkownika']);
        Permission::updateOrCreate(['name' => 'auth.sessions.show'], ['display_name' => 'Dostęp do listy sesji zalogowanego użytkownika']);
        Permission::updateOrCreate(['name' => 'auth.sessions.revoke'], ['display_name' => 'Możliwość blokowania sesji użytkownika']);

        // ProductSets
        Permission::updateOrCreate(['name' => 'product_sets.show'], ['display_name' => 'Dostęp do listy kolekcji']);
        Permission::updateOrCreate(['name' => 'product_sets.show_details'], ['display_name' => 'Dostęp do szczegółów kolekcji']);
        Permission::updateOrCreate(['name' => 'product_sets.show_hidden'], ['display_name' => 'Dostęp do ukrytych kolekcji']);
        Permission::updateOrCreate(['name' => 'product_sets.add'], ['display_name' => 'Możliwość tworzenia kolekcji']);
        Permission::updateOrCreate(['name' => 'product_sets.edit'], ['display_name' => 'Możliwość edycji kolekcji']);
        Permission::updateOrCreate(['name' => 'product_sets.remove'], ['display_name' => 'Możliwość usuwania kolekcji']);

        // Shipping Methods
        Permission::updateOrCreate(['name' => 'countries.show'], ['display_name' => 'Dostęp do listy krajów']);
        Permission::updateOrCreate(['name' => 'shipping_methods.show'], ['display_name' => 'Dostęp do listy metod dostawy']);
        Permission::updateOrCreate(['name' => 'shipping_methods.show_hidden'], ['display_name' => 'Dostęp do ukrytych metod dostawy']);
        Permission::updateOrCreate(['name' => 'shipping_methods.add'], ['display_name' => 'Możliwość tworzenia metod dostawy']);
        Permission::updateOrCreate(['name' => 'shipping_methods.edit'], ['display_name' => 'Możliwość edycji metod dostawy']);
        Permission::updateOrCreate(['name' => 'shipping_methods.remove'], ['display_name' => 'Możliwość usuwania metod dostawy']);

        // Deposits
        Permission::updateOrCreate(['name' => 'deposits.show'], [
            'display_name' => 'Dostęp do listy depozytów',
            'description' => 'Lista zmian ilości w przedmiotach magazynowych',
        ]);
        Permission::updateOrCreate(['name' => 'deposits.add'], [
            'display_name' => 'Możliwość tworzenia wpisu depozytowego',
            'description' => 'Zmiana ilości przedmiotów w magazynie',
        ]);

        // Discounts
        Permission::updateOrCreate(['name' => 'discounts.show'], ['display_name' => 'Dostęp do listy kodów rabatowych']);
        Permission::updateOrCreate(['name' => 'discounts.show_details'], [
            'display_name' => 'Dostęp do szczegółów kodów rabatowych',
            'description' => 'Sprawdzenie, czy zniżka o danym kodzie istnieje i pobranie jej szczegółów',
        ]);
        Permission::updateOrCreate(['name' => 'discounts.add'], ['display_name' => 'Możliwość tworzenia kodów rabatowych']);
        Permission::updateOrCreate(['name' => 'discounts.edit'], ['display_name' => 'Możliwość edycji kodów rabatowych']);
        Permission::updateOrCreate(['name' => 'discounts.remove'], ['display_name' => 'Możliwość usuwania kodów rabatowych']);

        // Items
        Permission::updateOrCreate(['name' => 'items.show'], ['display_name' => 'Dostęp do listy przedmiotów magazynowych']);
        Permission::updateOrCreate(['name' => 'items.show_details'], ['display_name' => 'Dostęp do szczegółów przedmiotów magazynowych']);
        Permission::updateOrCreate(['name' => 'items.add'], ['display_name' => 'Możliwość tworzenia przedmiotów magazynowych']);
        Permission::updateOrCreate(['name' => 'items.edit'], ['display_name' => 'Możliwość edycji przedmiotów magazynowych']);
        Permission::updateOrCreate(['name' => 'items.remove'], ['display_name' => 'Możliwość usuwania przedmiotów magazynowych']);

        // Schemas
        Permission::updateOrCreate(['name' => 'schemas.remove'], ['display_name' => 'Możliwość usuwania globalnych schematów']);

        // Orders
        Permission::updateOrCreate(['name' => 'cart.verify'], [
            'display_name' => 'Możliwość weryfikacji zawartości koszyka',
            'description' => 'Możliwość sprawdzenia, czy koszyk użytkownika może zostać zakupiony (Czy wszystkie jego elementy są dostępne itp.)',
        ]);
        Permission::updateOrCreate(['name' => 'orders.show'], ['display_name' => 'Dostęp do listy zamówień']);
        Permission::updateOrCreate(['name' => 'orders.show_details'], ['display_name' => 'Dostęp do szczegółów zamówień']);
        Permission::updateOrCreate(['name' => 'orders.show_summary'], ['display_name' => 'Dostęp do podsumowania zamówienia']);
        Permission::updateOrCreate(['name' => 'orders.add'], ['display_name' => 'Możliwość tworzenia zamówienia']);
        Permission::updateOrCreate(['name' => 'orders.edit'], ['display_name' => 'Możliwość edycji zamówienia']);
        Permission::updateOrCreate(['name' => 'orders.edit.status'], ['display_name' => 'Możliwość edycji statusu zamówień']);

        // Packages
        Permission::updateOrCreate(['name' => 'packages.show'], ['display_name' => 'Dostęp do listy szablonów przesyłek']);
        Permission::updateOrCreate(['name' => 'packages.add'], ['display_name' => 'Możliwość tworzenia szablonów przesyłek']);
        Permission::updateOrCreate(['name' => 'packages.edit'], ['display_name' => 'Możliwość usuwania szablonów przesyłek']);
        Permission::updateOrCreate(['name' => 'packages.remove'], ['display_name' => 'Możliwość edycji szablonów przesyłek']);

        // Pages
        Permission::updateOrCreate(['name' => 'pages.show'], ['display_name' => 'Dostęp do listy stron']);
        Permission::updateOrCreate(['name' => 'pages.show_details'], ['display_name' => 'Dostęp do szczegółów stron']);
        Permission::updateOrCreate(['name' => 'pages.show_hidden'], ['display_name' => 'Dostęp do ukrytych stron']);
        Permission::updateOrCreate(['name' => 'pages.add'], ['display_name' => 'Możliwość tworzenia stron']);
        Permission::updateOrCreate(['name' => 'pages.edit'], ['display_name' => 'Możliwość edycji stron']);
        Permission::updateOrCreate(['name' => 'pages.remove'], ['display_name' => 'Możliwość usuwania stron']);

        // Payments
        Permission::updateOrCreate(['name' => 'payments.add'], ['display_name' => 'Możliwość tworzenia transakcji']);
        Permission::updateOrCreate(['name' => 'payments.edit'], ['display_name' => 'Możliwość edycji transakcji']);
        Permission::updateOrCreate(['name' => 'payments.offline'], [
            'display_name' => 'Możliwość tworzenia transakcji offline',
            'description' => 'Ręczne opłacanie zamówień z panelu (np. gotówką)',
        ]);

        // Payment Methods
        Permission::updateOrCreate(['name' => 'payment_methods.show'], ['display_name' => 'Dostęp do listy metod płatności']);
        Permission::updateOrCreate(['name' => 'payment_methods.show_hidden'], ['display_name' => 'Dostęp do ukrytych metod płatności']);
        Permission::updateOrCreate(['name' => 'payment_methods.add'], ['display_name' => 'Możliwość tworzenia metod płatności']);
        Permission::updateOrCreate(['name' => 'payment_methods.edit'], ['display_name' => 'Możliwość edycji metod płatności']);
        Permission::updateOrCreate(['name' => 'payment_methods.remove'], ['display_name' => 'Możliwość usuwania metod płatności']);

        // Product
        Permission::updateOrCreate(['name' => 'products.show'], ['display_name' => 'Dostęp do listy produktów']);
        Permission::updateOrCreate(['name' => 'products.show_details'], ['display_name' => 'Dostęp do szczegółów produktów']);
        Permission::updateOrCreate(['name' => 'products.show_hidden'], ['display_name' => 'Dostęp do ukrytych produktów']);
        Permission::updateOrCreate(['name' => 'products.add'], ['display_name' => 'Możliwość tworzenia produktów']);
        Permission::updateOrCreate(['name' => 'products.edit'], ['display_name' => 'Możliwość edycji produktów']);
        Permission::updateOrCreate(['name' => 'products.remove'], ['display_name' => 'Możliwość usuwania produktów']);

        // Settings
        Permission::updateOrCreate(['name' => 'settings.show'], ['display_name' => 'Dostęp do listy ustawień zaawansowanych']);
        Permission::updateOrCreate(['name' => 'settings.show_details'], ['display_name' => 'Dostęp do szczegółów ustawień zaawansowanych']);
        Permission::updateOrCreate(['name' => 'settings.show_hidden'], ['display_name' => 'Dostęp do ukrytych ustawień zaawansowanych']);
        Permission::updateOrCreate(['name' => 'settings.add'], ['display_name' => 'Możliwość tworzenia ustawień zaawansowanych']);
        Permission::updateOrCreate(['name' => 'settings.edit'], ['display_name' => 'Możliwość edycji ustawień zaawansowanych']);
        Permission::updateOrCreate(['name' => 'settings.remove'], ['display_name' => 'Możliwość usuwania ustawień zaawansowanych']);

        // Statuses
        Permission::updateOrCreate(['name' => 'statuses.show'], ['display_name' => 'Dostęp do listy statusów zamówień']);
        Permission::updateOrCreate(['name' => 'statuses.add'], ['display_name' => 'Możliwość tworzenia statusów zamówień']);
        Permission::updateOrCreate(['name' => 'statuses.edit'], ['display_name' => 'Możliwość edycji statusów zamówień']);
        Permission::updateOrCreate(['name' => 'statuses.remove'], ['display_name' => 'Możliwość usuwania statusów zamówień']);

        // Tags
        Permission::updateOrCreate(['name' => 'tags.show'], ['display_name' => 'Dostęp do listy tagów']);
        Permission::updateOrCreate(['name' => 'tags.add'], ['display_name' => 'Możliwość tworzenia tagów']);
        Permission::updateOrCreate(['name' => 'tags.edit'], ['display_name' => 'Możliwość edycji tagów']);
        Permission::updateOrCreate(['name' => 'tags.remove'], ['display_name' => 'Możliwość usuwania tagów']);

        // Users
        Permission::updateOrCreate(['name' => 'users.show'], ['display_name' => 'Dostęp do listy użytkowników']);
        Permission::updateOrCreate(['name' => 'users.show_details'], ['display_name' => 'Dostęp do szczegółów użytkowników']);
        Permission::updateOrCreate(['name' => 'users.add'], ['display_name' => 'Możliwość tworzenia użytkowników']);
        Permission::updateOrCreate(['name' => 'users.edit'], ['display_name' => 'Możliwość edycji użytkowników']);
        Permission::updateOrCreate(['name' => 'users.remove'], ['display_name' => 'Możliwość usuwania użytkowników']);

        // Roles
        Permission::updateOrCreate(['name' => 'roles.show'], ['display_name' => 'Dostęp do listy ról użytkowników']);
        Permission::updateOrCreate(['name' => 'roles.show_details'], ['display_name' => 'Dostęp do szczegółów ról użytkowników']);
        Permission::updateOrCreate(['name' => 'roles.add'], ['display_name' => 'Możliwość tworzenia ról użytkowników']);
        Permission::updateOrCreate(['name' => 'roles.edit'], ['display_name' => 'Możliwość edycji ról użytkowników']);
        Permission::updateOrCreate(['name' => 'roles.remove'], ['display_name' => 'Możliwość usuwania ról użytkowników']);

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
