<?php

namespace Database\Seeders;

use App\Enums\AuthProviderKey;
use App\Enums\RoleType;
use App\Models\AuthProvider;
use App\Models\Role;
use App\Models\Status;
use App\Models\User;
use Domain\Language\Language;
use Domain\Seo\Models\SeoMetadata;
use Illuminate\Database\Seeder;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;

class InitSeeder extends Seeder
{
    public function run(): void
    {
        $language = $this->createLanguage()->getKey();
        $this->createUser();
        $this->createStatuses($language);

        $seeder = new CountriesSeeder();
        $seeder->run();

        $this->createGlobalSeo($language);
        $this->createAuthProviders();
    }

    private function createUser(): void
    {
        /** @var User $user */
        $user = User::factory()->create([
            'name' => 'Admin',
            'email' => 'admin@example.com',
            'password' => Hash::make('secret'),
        ]);

        $user->roles()->attach(
            Role::query()->where('type', RoleType::OWNER)->first()->getKey(),
        );
    }

    private function createStatuses(string $language): void
    {
        $published = [App::getLocale(), $language];
        /** @var Status $status */
        $status = Status::query()->create([
            'name' => 'Nowe',
            'color' => 'ffd600',
            'description' => 'Twoje zamówienie zostało zapisane w systemie!',
            'order' => 1,
            'published' => $published,
        ]);
        $status->setLocale($language)->fill([
            'name' => 'New',
            'description' => 'Your order has been saved in system!',
        ]);
        $status->save();

        /** @var Status $status */
        $status = Status::query()->create([
            'name' => 'Wysłane',
            'color' => '1faa00',
            'description' => 'Zamówienie zostało wysłane i wkrótce do trafi w Twoje ręce :)',
            'order' => 2,
            'published' => $published,
        ]);
        $status->setLocale($language)->fill([
            'name' => 'Sent',
            'description' => 'The order has been shipped and it will be in your hands soon :)',
        ]);
        $status->save();

        /** @var Status $status */
        $status = Status::query()->create([
            'name' => 'Anulowane',
            'color' => 'a30000',
            'description' => 'Twoje zamówienie zostało anulowane, jeśli to pomyłka, proszę skontaktuj się z nami',
            'order' => 3,
            'cancel' => true,
            'published' => $published,
        ]);
        $status->setLocale($language)->fill([
            'name' => 'Canceled',
            'description' => 'Your order has been canceled, if this is mistake, please contact us.',
        ]);
        $status->save();
    }

    private function createGlobalSeo(string $language): void
    {
        /** @var SeoMetadata $seo */
        $seo = SeoMetadata::query()->create([
            'global' => true,
        ]);
        $seoTranslation = SeoMetadata::factory()->definition();
        $seo->setLocale($language)->fill(Arr::only($seoTranslation, ['title', 'description', 'keywords', 'no_index']));
        $seo->fill(['published' => array_merge([App::getLocale(), $language])]);
        $seo->save();
        Cache::put('seo.global', $seo);
    }

    private function createAuthProviders(): void
    {
        $enums = Collection::make(AuthProviderKey::cases());
        $enums->each(fn (AuthProviderKey $enum) => AuthProvider::factory()->create([
            'key' => $enum->value,
            'active' => false,
            'client_id' => null,
            'client_secret' => null,
        ]));
    }

    private function createLanguage(): Language
    {
        return Language::query()->firstOrCreate([
            'iso' => 'en',
        ], [
            'name' => 'English',
            'hidden' => false,
            'default' => false,
        ]);
    }
}
