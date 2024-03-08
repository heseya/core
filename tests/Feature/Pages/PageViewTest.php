<?php

namespace Tests\Feature\Pages;

use Domain\Metadata\Enums\MetadataType;
use Domain\Page\Page;

class PageViewTest extends PageTestCase
{
    private Page $page_hidden;
    private array $expected_view;

    public function setUp(): void
    {
        parent::setUp();

        $this->page_hidden = Page::factory()->create([
            'public' => false,
        ]);

        $this->expected_view = array_merge($this->expected, [
            'content_html' => $this->page->content_html,
            'metadata' => [
                'Metadata' => 'metadata test',
            ],
        ]);
    }

    public function testViewUnauthorized(): void
    {
        $response = $this->getJson('/pages/' . $this->page->slug);
        $response->assertForbidden();

        $response = $this->getJson('/pages/id:' . $this->page->getKey());
        $response->assertForbidden();
    }

    /**
     * @dataProvider authProvider
     */
    public function testView(string $user): void
    {
        $this->{$user}->givePermissionTo('pages.show_details');

        $response = $this->actingAs($this->{$user})
            ->getJson('/pages/' . $this->page->slug);
        $response
            ->assertOk()
            ->assertJson(['data' => $this->expected_view]);

        $response = $this->actingAs($this->{$user})
            ->getJson('/pages/id:' . $this->page->getKey());
        $response
            ->assertOk()
            ->assertJson(['data' => $this->expected_view]);
    }

    /**
     * @dataProvider authProvider
     */
    public function testViewWrongIdOrSlug(string $user): void
    {
        $this->{$user}->givePermissionTo('pages.show_details');

        $this->actingAs($this->{$user})
            ->getJson('/pages/its_wrong_slug')
            ->assertNotFound();

        $this->actingAs($this->{$user})
            ->getJson('/pages/id:its-not-uuid')
            ->assertNotFound();

        $this->actingAs($this->{$user})
            ->getJson('/pages/id:' . $this->page->getKey() . $this->page->getKey())
            ->assertNotFound();
    }

    /**
     * @dataProvider authProvider
     */
    public function testViewPrivateMetadata(string $user): void
    {
        $this->{$user}->givePermissionTo(['pages.show_details', 'pages.show_metadata_private']);

        $privateMetadata = $this->page->metadataPrivate()->create([
            'name' => 'hiddenMetadata',
            'value' => 'hidden metadata test',
            'value_type' => MetadataType::STRING,
            'public' => false,
        ]);

        $response = $this->actingAs($this->{$user})
            ->getJson('/pages/' . $this->page->slug);
        $response
            ->assertOk()
            ->assertJson(['data' => $this->expected_view]);

        $response = $this->actingAs($this->{$user})
            ->getJson('/pages/id:' . $this->page->getKey());
        $response
            ->assertOk()
            ->assertJson([
                'data' => $this->expected_view +
                    [
                        'metadata_private' => [
                            $privateMetadata->name => $privateMetadata->value,
                        ],
                    ],
            ]);
    }

    /**
     * @dataProvider authProvider
     */
    public function testViewHiddenUnauthorized(string $user): void
    {
        $this->{$user}->givePermissionTo('pages.show_details');

        $response = $this->actingAs($this->{$user})
            ->getJson('/pages/' . $this->page_hidden->slug);
        $response->assertNotFound();

        $response = $this->actingAs($this->{$user})
            ->getJson('/pages/id:' . $this->page_hidden->getKey());
        $response->assertNotFound();
    }

    /**
     * @dataProvider authProvider
     */
    public function testViewHidden(string $user): void
    {
        $this->{$user}->givePermissionTo(['pages.show_details', 'pages.show_hidden']);

        $response = $this->actingAs($this->{$user})
            ->getJson('/pages/' . $this->page_hidden->slug);
        $response->assertOk();

        $response = $this->actingAs($this->{$user})
            ->getJson('/pages/id:' . $this->page_hidden->getKey());
        $response->assertOk();
    }
}
