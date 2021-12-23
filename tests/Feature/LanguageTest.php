<?php

namespace Tests\Feature;

use App\Models\Language;
use Tests\TestCase;

class LanguageTest extends TestCase
{
    public Language $language;
    public Language $languageHidden;

    public function setUp(): void
    {
        parent::setUp();

        Language::query()->delete();

        $this->language = Language::create([
            'iso' => 'pl',
            'name' => 'Polski',
            'default' => true,
            'hidden' => false,
        ]);

        $this->languageHidden = Language::create([
            'iso' => 'en',
            'name' => 'English',
            'default' => false,
            'hidden' => true,
        ]);
    }

    public function testIndex(): void
    {
        $this
            ->json('GET', '/languages')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonFragment(['id' => $this->language->getKey()])
            ->assertJsonMissing(['id' => $this->languageHidden->getKey()]);
    }

    /**
     * @dataProvider authProvider
     */
    public function testIndexHidden($user): void
    {
        $this->$user->givePermissionTo('languages.show_hidden');

        $this
            ->actingAs($this->$user)
            ->json('GET', '/languages')
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonFragment(['id' => $this->language->getKey()])
            ->assertJsonFragment(['id' => $this->languageHidden->getKey()]);
    }

    /**
     * @dataProvider authProvider
     */
    public function testCreateWithoutPermissions($user): void
    {
        $this
            ->actingAs($this->$user)
            ->json('POST', '/languages', [
                'iso' => 'es',
                'name' => 'Spain',
                'hidden' => false,
                'default' => true,
            ])
            ->assertUnauthorized();
    }

    /**
     * @dataProvider authProvider
     */
    public function testCreate($user): void
    {
        $this->$user->givePermissionTo('languages.add');

        $this
            ->actingAs($this->$user)
            ->json('POST', '/languages', [
                'iso' => 'es',
                'name' => 'Spain',
                'hidden' => false,
                'default' => true,
            ])
            ->assertCreated();

        $this->assertDatabaseHas('languages', [
            'iso' => 'es',
            'name' => 'Spain',
            'hidden' => false,
            'default' => true,
        ]);
        $this->assertDatabaseMissing('languages', [
            'iso' => 'pl',
            'default' => false,
        ]);
        $this->assertDatabaseMissing('languages', [
            'iso' => 'de',
            'default' => false,
        ]);

        $this->assertEquals('es', Language::default()->iso);
    }
}
