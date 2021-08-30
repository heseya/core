<?php

namespace Tests\Feature;

use Tests\TestCase;

class SettingsTest extends TestCase
{
    public function testIndexUnauthorized(): void
    {
        $this->getJson('/settings')->assertForbidden();
    }

    public function testIndex(): void
    {
        $this->user->givePermissionTo('settings.show');

        $this->actingAs($this->user)->getJson('/settings')->assertOk();
    }

    public function testViewUnauthorized(): void
    {
        $this->getJson('/settings/store_name')->assertForbidden();
    }

    public function testView(): void
    {
        $this->user->givePermissionTo('settings.show_details');

        $this->actingAs($this->user)->getJson('/settings/store_name')->assertOk();
    }
}
