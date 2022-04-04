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
    public function testIndex($user): void
    {
        $this->$user->givePermissionTo('settings.show');

        $this->actingAs($this->$user)->getJson('/settings')->assertOk();
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
