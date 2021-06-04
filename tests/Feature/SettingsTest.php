<?php

namespace Tests\Feature;

use Tests\TestCase;

class SettingsTest extends TestCase
{
    public function testIndex(): void
    {
        $response = $this->getJson('/settings');

        $response->assertOk();
    }

    public function testView(): void
    {
        $response = $this->getJson('/settings/store_name');

        $response->assertOk();
    }
}
