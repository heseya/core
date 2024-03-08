<?php

namespace Tests\Feature\Pages;

use Domain\Page\Events\PageCreated;
use Domain\Page\Events\PageDeleted;
use Domain\Seo\Models\SeoMetadata;
use Illuminate\Support\Facades\Event;

class PageDeleteTest extends PageTestCase
{
    public function testDeleteUnauthorized(): void
    {
        Event::fake(PageDeleted::class);

        $page = $this->page->only(['name', 'slug', 'public', 'content_html', 'id']);
        unset($page['content_html'], $page['name'], $page['published']);

        $response = $this->patchJson('/pages/id:' . $this->page->getKey());
        $response->assertForbidden();
        $this->assertDatabaseHas('pages', $page + [
                "name->{$this->lang}" => $this->page->name,
                "content_html->{$this->lang}" => $this->page->content_html,
            ]);

        Event::assertNotDispatched(PageDeleted::class);
    }

    /**
     * @dataProvider authProvider
     */
    public function testDelete(string $user): void
    {
        $this->{$user}->givePermissionTo('pages.remove');

        $seo = SeoMetadata::factory()->create();
        $this->page->seo()->save($seo);

        Event::fake([PageDeleted::class]);

        $response = $this->actingAs($this->{$user})
            ->deleteJson('/pages/id:' . $this->page->getKey());
        $response->assertNoContent();
        $this->assertSoftDeleted($this->page);
        $this->assertSoftDeleted($seo);

        Event::assertDispatched(PageDeleted::class);
    }
}
