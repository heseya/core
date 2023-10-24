<?php

namespace Tests\Feature;

use App\Enums\CacheTime;
use App\Enums\RoleType;
use App\Models\Role;
use Domain\Page\Page;
use Domain\ProductSet\ProductSet;
use Tests\TestCase;

class CacheResponseTest extends TestCase
{
    /**
     * @return array<string, array<int, mixed>>
     */
    public static function longCachePublicProvider(): array
    {
        return [
            'countries' => ['/countries', 'countries.show'],
            'payment-methods' => ['/payment-methods', 'payment_methods.show'],
            'seo' => ['/seo', null],
        ];
    }

    /**
     * @dataProvider longCachePublicProvider
     */
    public function testLongCachePublic(string $url, string|null $permission): void
    {
        if ($permission) {
            $role = Role::query()->where('type', RoleType::UNAUTHENTICATED)->firstOrFail();
            $role->givePermissionTo($permission);
        }

        $this
            ->json('GET', $url)
            ->assertOk()
            ->assertHeader('Cache-Control', 'max-age=' . CacheTime::LONG_TIME->value . ', private');
    }

    /**
     * @dataProvider longCachePublicProvider
     */
    public function testLongCachePublicWithAuth(string $url, string|null $permission): void
    {
        if ($permission) {
            $this->user->givePermissionTo($permission);
        }

        $this
            ->actingAs($this->user)
            ->json('GET', $url)
            ->assertOk()
            ->assertHeader('Cache-Control', 'no-cache, private');
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public static function longCacheAuthProvider(): array
    {
        return [
            'shipping-methods' => ['/shipping-methods', 'shipping_methods.show'],
            'pages' => ['/pages', 'pages.show'],
            'product-sets' => ['/product-sets', 'product_sets.show'],
            'settings' => ['/settings', 'settings.show'],
        ];
    }

    /**
     * @dataProvider longCacheAuthProvider
     */
    public function testLongCacheAuthResponse(string $url, string $permission): void
    {
        $this->user->givePermissionTo($permission);

        $this
            ->actingAs($this->user)
            ->json('GET', $url)
            ->assertOk()
            ->assertHeader('Cache-Control', 'max-age=' . CacheTime::LONG_TIME->value . ', private');
    }

    /**
     * @dataProvider longCacheAuthProvider
     */
    public function testLongCacheNoAuth(string $url, string $permission): void
    {
        $role = Role::query()->where('type', RoleType::UNAUTHENTICATED)->firstOrFail();
        $role->givePermissionTo($permission);

        $this
            ->json('GET', $url)
            ->assertOk()
            ->assertHeader('Cache-Control', 'no-cache, private');
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public static function shortCachePublicProvider(): array
    {
        return [
            'consents' => ['/consents', 'consents.show'],
            'permissions' => ['/permissions', 'roles.show_details'],
            'filters' => ['/filters', null],
        ];
    }

    /**
     * @dataProvider shortCachePublicProvider
     */
    public function testShortCachePublic(string $url, string|null $permission): void
    {
        if ($permission) {
            $role = Role::query()->where('type', RoleType::UNAUTHENTICATED)->firstOrFail();
            $role->givePermissionTo($permission);
        }

        $this
            ->json('GET', $url)
            ->assertOk()
            ->assertHeader('Cache-Control', 'max-age=' . CacheTime::SHORT_TIME->value . ', private');
    }

    /**
     * @dataProvider shortCachePublicProvider
     */
    public function testShortCachePublicWithAuth(string $url, string|null $permission): void
    {
        if ($permission) {
            $this->user->givePermissionTo($permission);
        }

        $this
            ->actingAs($this->user)
            ->json('GET', $url)
            ->assertOk()
            ->assertHeader('Cache-Control', 'no-cache, private');
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public static function shortCacheAuthProvider(): array
    {
        return [
            'banners' => ['/banners', 'banners.show'],
        ];
    }

    /**
     * @dataProvider shortCacheAuthProvider
     */
    public function testShortCacheAuthResponse(string $url, string $permission): void
    {
        $this->user->givePermissionTo($permission);

        $this
            ->actingAs($this->user)
            ->json('GET', $url)
            ->assertOk()
            ->assertHeader('Cache-Control', 'max-age=' . CacheTime::SHORT_TIME->value . ', private');
    }

    /**
     * @dataProvider shortCacheAuthProvider
     */
    public function testShortCacheNoAuth(string $url, string $permission): void
    {
        $role = Role::query()->where('type', RoleType::UNAUTHENTICATED)->firstOrFail();
        $role->givePermissionTo($permission);

        $this
            ->json('GET', $url)
            ->assertOk()
            ->assertHeader('Cache-Control', 'no-cache, private');
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public static function veryShortCacheAuthProvider(): array
    {
        return [
            'products' => ['/products', 'products.show'],
        ];
    }

    /**
     * @dataProvider veryShortCacheAuthProvider
     */
    public function testVeryShortCacheAuthResponse(string $url, string $permission): void
    {
        $this->user->givePermissionTo($permission);

        $this
            ->actingAs($this->user)
            ->json('GET', $url)
            ->assertOk()
            ->assertHeader('Cache-Control', 'max-age=' . CacheTime::VERY_SHORT_TIME->value . ', private');
    }

    /**
     * @dataProvider veryShortCacheAuthProvider
     */
    public function testVeryShortCacheNoAuth(string $url, string $permission): void
    {
        $role = Role::query()->where('type', RoleType::UNAUTHENTICATED)->firstOrFail();
        $role->givePermissionTo($permission);

        $this
            ->json('GET', $url)
            ->assertOk()
            ->assertHeader('Cache-Control', 'no-cache, private');
    }

    public function testPagesSlugCache(): void
    {
        $role = Role::query()->where('type', RoleType::UNAUTHENTICATED)->firstOrFail();
        $role->givePermissionTo('pages.show_details');

        $page = Page::factory()->create([
            'public' => true,
        ]);

        $this
            ->json('GET', '/pages/' . $page->slug)
            ->assertOk()
            ->assertHeader('Cache-Control', 'max-age=' . CacheTime::LONG_TIME->value . ', private');
    }

    public function testPagesIdCache(): void
    {
        $role = Role::query()->where('type', RoleType::UNAUTHENTICATED)->firstOrFail();
        $role->givePermissionTo('pages.show_details');

        $page = Page::factory()->create([
            'public' => true,
        ]);

        $this
            ->json('GET', '/pages/id:' . $page->getKey())
            ->assertOk()
            ->assertHeader('Cache-Control', 'no-cache, private');
    }

    public function testPagesSlugCacheWithAuth(): void
    {
        $this->user->givePermissionTo('pages.show_details');

        $page = Page::factory()->create([
            'public' => true,
        ]);

        $this
            ->actingAs($this->user)
            ->json('GET', '/pages/' . $page->slug)
            ->assertOk()
            ->assertHeader('Cache-Control', 'no-cache, private');
    }

    public function testProductSetSlugCache(): void
    {
        $this->user->givePermissionTo('product_sets.show_details');

        $set = ProductSet::factory()->create([
            'public' => true,
        ]);

        $this
            ->actingAs($this->user)
            ->json('GET', '/product-sets/' . $set->slug)
            ->assertOk()
            ->assertHeader('Cache-Control', 'max-age=' . CacheTime::LONG_TIME->value . ', private');
    }

    public function testProductSetIdCache(): void
    {
        $this->user->givePermissionTo('product_sets.show_details');

        $set = ProductSet::factory()->create([
            'public' => true,
        ]);

        $this
            ->actingAs($this->user)
            ->json('GET', '/product-sets/id:' . $set->getKey())
            ->assertOk()
            ->assertHeader('Cache-Control', 'no-cache, private');
    }

    public function testProductSetSlugCacheWithAuth(): void
    {
        $role = Role::query()->where('type', RoleType::UNAUTHENTICATED)->firstOrFail();
        $role->givePermissionTo('product_sets.show_details');

        $set = ProductSet::factory()->create([
            'public' => true,
        ]);

        $this
            ->json('GET', '/product-sets/' . $set->slug)
            ->assertOk()
            ->assertHeader('Cache-Control', 'no-cache, private');
    }
}
