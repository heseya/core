<?php

namespace Tests\Feature;

use Tests\TestCase;

class CountryTest extends TestCase
{
    public function testIndex(): void
    {
        $response = $this->getJson('/countries');

        $response->assertOk();
    }
}
