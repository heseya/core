<?php

namespace Tests\Feature\Pages;

use Domain\Page\Page;

class PageIndexTest extends PageTestCase
{
    public function testIndexUnauthorized(): void
    {
        $response = $this->getJson('/pages');
        $response->assertForbidden();
    }

    /**
     * @dataProvider authProvider
     */
    public function testIndex(string $user): void
    {
        $this->{$user}->givePermissionTo('pages.show');

        $this
            ->actingAs($this->{$user})
            ->getJson('/pages')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJson(['data' => [
                0 => $this->expected,
            ]]);

        $this->assertQueryCountLessThan(11);
    }

    /**
     * @dataProvider authProvider
     */
    public function testIndexWithTranslationsFlag(string $user): void
    {
        $this->{$user}->givePermissionTo('pages.show');

        $response = $this
            ->actingAs($this->{$user})
            ->getJson('/pages?with_translations=1');

        $response
            ->assertOk()
            ->assertJsonCount(1, 'data');

        $firstElement = $response->json('data.0');

        $this->assertArrayHasKey('translations', $firstElement);
        $this->assertIsArray($firstElement['translations']);

        $this->assertQueryCountLessThan(11);
    }

    /**
     * @dataProvider authProvider
     */
    public function testIndexWithTranslationsFlag2(string $user): void
    {
        $this->{$user}->givePermissionTo('pages.show');

        $response = $this
            ->actingAs($this->{$user})
            ->getJson('/pages?with_translations=1');

        $response
            ->assertOk()
            ->assertJsonCount(1, 'data');

        $firstElement = $response['data'][0];

        $this->assertArrayHasKey('translations', $firstElement);
        $this->assertIsArray($firstElement['translations']);

        $this->assertQueryCountLessThan(11);
    }

    /**
     * @dataProvider authProvider
     */
    public function testIndexByIds(string $user): void
    {
        $this->{$user}->givePermissionTo('pages.show');

        Page::factory()->count(10)->create();

        $this
            ->actingAs($this->{$user})
            ->json('GET', '/pages', [
                'ids' => [
                    $this->page->getKey(),
                ],
            ])
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJson([
                'data' => [
                    0 => $this->expected,
                ],
            ]);

        $this->assertQueryCountLessThan(11);
    }

    /**
     * @dataProvider authProvider
     */
    public function testIndexSearch(string $user): void
    {
        $this->{$user}->givePermissionTo('pages.show');

        Page::factory()->count(10)->create();

        $this->page->update([
            'name' => 'Searched name',
        ]);

        $this
            ->actingAs($this->{$user})
            ->json('GET', '/pages', [
                'search' => $this->page->name,
            ])
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonFragment([
                'id' => $this->page->getKey(),
                'name' => 'Searched name',
            ]);

        $this->assertQueryCountLessThan(11);
    }

    /**
     * @dataProvider authProvider
     */
    public function testIndexSearchSlug(string $user): void
    {
        $this->{$user}->givePermissionTo('pages.show');

        Page::factory()->count(10)->create();

        $this->page->update([
            'slug' => 'searched-slug',
        ]);

        $this
            ->actingAs($this->{$user})
            ->json('GET', '/pages', [
                'search' => $this->page->slug,
            ])
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonFragment([
                'id' => $this->page->getKey(),
                'slug' => 'searched-slug',
            ]);

        $this->assertQueryCountLessThan(11);
    }

    /**
     * @dataProvider authProvider
     */
    public function testIndexPerformance(string $user): void
    {
        $this->{$user}->givePermissionTo('pages.show');

        Page::factory()->count(499)->create(['public' => true]);

        $this
            ->actingAs($this->{$user})
            ->getJson('/pages?limit=500')
            ->assertOk()
            ->assertJsonCount(500, 'data');

        $this->assertQueryCountLessThan(11);
    }

    /**
     * @dataProvider authProvider
     */
    public function testIndexHidden(string $user): void
    {
        $this->{$user}->givePermissionTo(['pages.show', 'pages.show_hidden']);

        $pageHidden = Page::factory()->create([
            'public' => false,
        ]);

        $response = $this->actingAs($this->{$user})->getJson('/pages');
        $response
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonFragment([
                'id' => $pageHidden->getKey(),
            ])
            ->assertJsonFragment([
                'id' => $this->page->getKey(),
            ]);
    }
}
