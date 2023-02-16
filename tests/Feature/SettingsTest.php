<?php

namespace Tests\Feature;

use App\Models\Setting;
use Tests\TestCase;

class SettingsTest extends TestCase
{
    public function testIndexUnauthorized(): void
    {
        $this->getJson('/settings')->assertForbidden();
    }

    /**
     * @dataProvider authProvider
     */
    public function testIndexPublic($user): void
    {
        $this->$user->givePermissionTo('settings.show');

        Setting::create([
            'name' => 'private_setting',
            'value' => 'Private value',
            'public' => false,
        ]);

        $this
            ->actingAs($this->$user)
            ->getJson('/settings')
            ->assertOk()
            ->assertJsonMissing([
                'name' => 'private_setting',
            ]);
    }

    /**
     * @dataProvider authProvider
     */
    public function testIndexPrivate($user): void
    {
        $this->$user->givePermissionTo(['settings.show', 'settings.show_hidden']);

        Setting::create([
            'name' => 'private_setting',
            'value' => 'Private value',
            'public' => false,
        ]);

        $this
            ->actingAs($this->$user)
            ->getJson('/settings')
            ->assertOk()
            ->assertJsonFragment([
                'name' => 'private_setting',
            ]);
    }

    public function testViewUnauthorized(): void
    {
        $this->getJson('/settings/store_name')->assertForbidden();
    }

    /**
     * @dataProvider authProvider
     */
    public function testView($user): void
    {
        $this->$user->givePermissionTo('settings.show_details');

        $this->actingAs($this->$user)->getJson('/settings/store_name')->assertOk();
    }

    /**
     * @dataProvider authProvider
     */
    public function testViewPrivateUnauthorized($user): void
    {
        $this->$user->givePermissionTo('settings.show_details');

        Setting::create([
            'name' => 'private_setting',
            'value' => 'Private value',
            'public' => false,
        ]);

        $this->actingAs($this->$user)->getJson('/settings/private_setting')->assertNotFound();
    }

    /**
     * @dataProvider authProvider
     */
    public function testViewPrivateAuthorized($user): void
    {
        $this->$user->givePermissionTo(['settings.show_details', 'settings.show_hidden']);

        Setting::create([
            'name' => 'private_setting',
            'value' => 'Private value',
            'public' => false,
        ]);

        $this->actingAs($this->$user)->getJson('/settings/private_setting')->assertOk();
    }

    /**
     * @dataProvider authProvider
     */
    public function testViewWrongSetting($user): void
    {
        $this->$user->givePermissionTo('settings.show_details');

        $this
            ->actingAs($this->$user)
            ->getJson('/settings/it\'s-wrong%parameter')
            ->assertNotFound();
    }

    public function testCreateUnauthorized(): void
    {
        $setting = [
            'name' => 'store_name',
            'value' => 'Heseya Store',
            'public' => true,
        ];

        $this->json('POST', '/settings', $setting)->assertForbidden();
    }

    /**
     * @dataProvider authProvider
     */
    public function testCreate($user): void
    {
        $this->$user->givePermissionTo('settings.add');

        $setting = [
            'name' => 'new_setting',
            'value' => 'New Value',
            'public' => true,
        ];

        $this->actingAs($this->$user)->json('POST', '/settings', $setting)
            ->assertCreated()->assertJsonFragment($setting);

        $this->assertDatabaseHas('settings', $setting);
    }

    /**
     * @dataProvider authProvider
     */
    public function testCreateConfigSettings($user): void
    {
        $this->$user->givePermissionTo('settings.add');

        $setting = [
            'name' => 'store_name',
            'value' => 'New Value',
            'public' => true,
        ];

        $this->actingAs($this->$user)->json('POST', '/settings', $setting)
            ->assertStatus(422);
    }

    public function testUpdateUnauthorized(): void
    {
        $setting = [
            'name' => 'store_name',
            'value' => 'Heseya Store',
            'public' => true,
        ];

        $this->json('PATCH', '/settings/store_name', $setting)->assertForbidden();
    }

    /**
     * @dataProvider authProvider
     */
    public function testUpdateConfigSetting($user): void
    {
        $this->$user->givePermissionTo('settings.edit');

        $setting = [
            'name' => 'store_name',
            'value' => 'Heseya Store',
            'public' => true,
        ];

        $this->actingAs($this->$user)->json('PATCH', '/settings/store_name', $setting)
            ->assertCreated()->assertJsonFragment($setting);

        $this->assertDatabaseHas('settings', $setting);
    }

    /**
     * @dataProvider authProvider
     */
    public function testUpdateDatabaseSetting($user): void
    {
        $this->$user->givePermissionTo('settings.edit');

        Setting::create([
            'name' => 'new_setting',
            'value' => 'Old Value',
            'public' => true,
        ]);

        $new_setting = [
            'name' => 'new_setting',
            'value' => 'New Value',
            'public' => false,
        ];

        $this->actingAs($this->$user)->json('PATCH', '/settings/new_setting', $new_setting)
            ->assertOk()->assertJsonFragment($new_setting);

        $this->assertDatabaseHas('settings', $new_setting);
    }

    /**
     * @dataProvider authProvider
     */
    public function testUpdateDatabaseSettingWithEmptyData($user): void
    {
        $this->$user->givePermissionTo('settings.edit');

        $setting = Setting::create([
            'name' => 'new_setting',
            'value' => 'Old Value',
            'public' => true,
        ]);

        $this->actingAs($this->$user)->json('PATCH', '/settings/new_setting', [])
            ->assertOk();

        $this->assertDatabaseHas('settings', $setting->toArray());
    }

    /**
     * @dataProvider authProvider
     */
    public function testDelete($user): void
    {
        $this->$user->givePermissionTo('settings.remove');

        $setting = Setting::create([
            'name' => 'new_setting',
            'value' => 'Old Value',
            'public' => true,
        ]);

        $this->actingAs($this->$user)->json('DELETE', '/settings/new_setting')
            ->assertNoContent();

        $this->assertDatabaseMissing('settings', [
            'id' => $setting->getKey(),
        ]);
    }
}
