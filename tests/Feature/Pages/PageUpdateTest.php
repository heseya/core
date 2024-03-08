<?php

namespace Tests\Feature\Pages;

use App\Listeners\WebHookEventListener;
use App\Models\WebHook;
use Domain\Language\Language;
use Domain\Page\Events\PageUpdated;
use Domain\Seo\Models\SeoMetadata;
use Illuminate\Events\CallQueuedListener;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Event;

class PageUpdateTest extends PageTestCase
{
    public function testUpdateUnauthorized(): void
    {
        Event::fake(PageUpdated::class);

        $response = $this->patchJson('/pages/id:' . $this->page->getKey());
        $response->assertForbidden();

        Event::assertNotDispatched(PageUpdated::class);
    }

    /**
     * @dataProvider authProvider
     */
    public function testUpdate(string $user): void
    {
        $this->{$user}->givePermissionTo('pages.edit');

        Event::fake(PageUpdated::class);

        $html = '<h1>hello world 2</h1>';

        $this
            ->actingAs($this->{$user})
            ->patchJson('/pages/id:' . $this->page->getKey(), [
                'slug' => 'test-2',
                'public' => false,
                'translations' => [
                    $this->lang => [
                        'name' => 'Test 2',
                        'content_html' => $html,
                    ],
                ],
                'published' => [$this->lang],
            ])
            ->assertOk()
            ->assertJson(['data' => [
                'name' => 'Test 2',
                'slug' => 'test-2',
                'public' => false,
                'content_html' => $html,
            ]]);

        $this->assertDatabaseHas('pages', [
            'id' => $this->page->getKey(),
            "name->{$this->lang}" => 'Test 2',
            'slug' => 'test-2',
            'public' => false,
            "content_html->{$this->lang}" => $html,
        ]);

        Event::assertDispatched(PageUpdated::class);
    }

    /**
     * @dataProvider authProvider
     */
    public function testUpdatePublished(string $user): void
    {
        $this->{$user}->givePermissionTo('pages.edit');

        Event::fake(PageUpdated::class);

        $html = '<h1>hello world 2</h1>';

        $this->page->update([
            'published' => Language::query()->get()->pluck('id'),
        ]);

        $this
            ->actingAs($this->{$user})
            ->patchJson('/pages/id:' . $this->page->getKey(), [
                'slug' => 'test-2',
                'public' => false,
                'translations' => [
                    $this->lang => [
                        'name' => 'Test 2',
                        'content_html' => $html,
                    ],
                ],
                'published' => [$this->lang],
            ])
            ->assertOk()
            ->assertJson(['data' => [
                'name' => 'Test 2',
                'slug' => 'test-2',
                'public' => false,
                'content_html' => $html,
                'published' => [
                    $this->lang,
                ],
            ]]);

        $this->assertDatabaseHas('pages', [
            'id' => $this->page->getKey(),
            "name->{$this->lang}" => 'Test 2',
            'slug' => 'test-2',
            'public' => false,
            "content_html->{$this->lang}" => $html,
        ]);

        Event::assertDispatched(PageUpdated::class);
    }

    /**
     * @dataProvider authProvider
     */
    public function testUpdateSameSlug(string $user): void
    {
        $this->{$user}->givePermissionTo('pages.edit');

        Event::fake(PageUpdated::class);

        $html = '<h1>hello world 2</h1>';

        $this
            ->actingAs($this->{$user})
            ->patchJson('/pages/id:' . $this->page->getKey(), [
                'slug' => $this->page->slug,
                'public' => false,
                'translations' => [
                    $this->lang => [
                        'name' => 'Test 2',
                        'content_html' => $html,
                    ],
                ],
                'published' => [$this->lang],
            ])
            ->assertOk()
            ->assertJson(['data' => [
                'name' => 'Test 2',
                'slug' => $this->page->slug,
                'public' => false,
                'content_html' => $html,
            ]]);

        $this->assertDatabaseHas('pages', [
            'id' => $this->page->getKey(),
            "name->{$this->lang}" => 'Test 2',
            'slug' => $this->page->slug,
            'public' => false,
            "content_html->{$this->lang}" => $html,
        ]);

        Event::assertDispatched(PageUpdated::class);
    }

    /**
     * @dataProvider authProvider
     */
    public function testUpdateMissingFields(string $user): void
    {
        $this->{$user}->givePermissionTo('pages.edit');
        $this
            ->actingAs($this->{$user})
            ->patchJson('/pages/id:' . $this->page->getKey(), [
                'public' => true,
            ])
            ->assertOk();
    }

    /**
     * @dataProvider authProvider
     */
    public function testUpdateWithWebHook(string $user): void
    {
        $this->{$user}->givePermissionTo('pages.edit');

        $webHook = WebHook::factory()->create([
            'events' => [
                'PageUpdated',
            ],
            'model_type' => $this->{$user}::class,
            'creator_id' => $this->{$user}->getKey(),
            'with_issuer' => true,
            'with_hidden' => true,
        ]);

        Bus::fake();

        $html = '<h1>hello world 2</h1>';

        $response = $this
            ->actingAs($this->{$user})
            ->patchJson('/pages/id:' . $this->page->getKey(), [
                'slug' => 'test-2',
                'public' => false,
                'translations' => [
                    $this->lang => [
                        'name' => 'Test 2',
                        'content_html' => $html,
                    ],
                ],
                'published' => [$this->lang],
            ])
            ->assertOk()
            ->assertJson(['data' => [
                'name' => 'Test 2',
                'slug' => 'test-2',
                'public' => false,
                'content_html' => $html,
            ]]);

        $this->assertDatabaseHas('pages', [
            'id' => $this->page->getKey(),
            "name->{$this->lang}" => 'Test 2',
            'slug' => 'test-2',
            'public' => false,
            "content_html->{$this->lang}" => $html,
        ]);

        Bus::assertDispatched(CallQueuedListener::class, function ($job) {
            return $job->class === WebHookEventListener::class
                && $job->data[0] instanceof PageUpdated;
        });
    }

    /**
     * @dataProvider authWithTwoBooleansProvider
     */
    public function testUpdateWithSeo(string $user, bool $boolean, bool $booleanValue): void
    {
        $this->{$user}->givePermissionTo('pages.edit');

        $this->page->seo()->save(SeoMetadata::factory()->make());
        $this
            ->actingAs($this->{$user})
            ->json('PATCH', '/pages/id:' . $this->page->getKey(), [
                'translations' => [
                    $this->lang => [
                        'name' => 'Test 2',
                        'content_html' => '<h1>hello world 2</h1>',
                    ],
                ],
                'published' => [$this->lang],
                'slug' => 'test-2',
                'public' => $boolean,
                'seo' => [
                    'translations' => [
                        $this->lang => [
                            'title' => 'seo title',
                            'description' => 'seo description',
                            'no_index' => $boolean,
                        ],
                    ],
                    'published' => [$this->lang],
                ],
            ])
            ->assertOk()
            ->assertJsonFragment([
                'title' => 'seo title',
                'description' => 'seo description',
                'no_index' => $booleanValue,
            ])->assertJsonFragment([
                'name' => 'Test 2',
                'slug' => 'test-2',
                'public' => $booleanValue,
                'content_html' => '<h1>hello world 2</h1>',
            ]);

        $this->assertDatabaseHas('pages', [
            'id' => $this->page->getKey(),
            "name->{$this->lang}" => 'Test 2',
            'slug' => 'test-2',
            'public' => $booleanValue,
            "content_html->{$this->lang}" => '<h1>hello world 2</h1>',
        ]);
        $this->assertDatabaseHas('seo_metadata', [
            "title->{$this->lang}" => 'seo title',
            "description->{$this->lang}" => 'seo description',
            "no_index->{$this->lang}" => $booleanValue,
        ]);
    }

    /**
     * @dataProvider authWithTwoBooleansProvider
     */
    public function testUpdateSeo(string $user, bool $boolean, bool $booleanValue): void
    {
        $this->{$user}->givePermissionTo('pages.edit');

        $this
            ->actingAs($this->{$user})
            ->json('PATCH', '/pages/id:' . $this->page->getKey(), [
                'translations' => [
                    $this->lang => [
                        'name' => 'Test 2',
                        'content_html' => '<h1>hello world 2</h1>',
                    ],
                ],
                'published' => [$this->lang],
                'slug' => 'test-2',
                'public' => $boolean,
                'seo' => [
                    'translations' => [
                        $this->lang => [
                            'title' => 'seo title',
                            'description' => 'seo description',
                            'no_index' => $boolean,
                        ],
                    ],
                    'published' => [$this->lang],
                ],
            ])
            ->assertOk()
            ->assertJsonFragment([
                'title' => 'seo title',
                'description' => 'seo description',
                'no_index' => $booleanValue,
            ])
            ->assertJsonFragment([
                'name' => 'Test 2',
                'slug' => 'test-2',
                'public' => $booleanValue,
                'content_html' => '<h1>hello world 2</h1>',
            ]);

        $this->assertDatabaseHas('pages', [
            'id' => $this->page->getKey(),
            "name->{$this->lang}" => 'Test 2',
            'slug' => 'test-2',
            'public' => $booleanValue,
            "content_html->{$this->lang}" => '<h1>hello world 2</h1>',
        ]);
        $this->assertDatabaseHas('seo_metadata', [
            "title->{$this->lang}" => 'seo title',
            "description->{$this->lang}" => 'seo description',
            "no_index->{$this->lang}" => $booleanValue,
            'model_id' => $this->page->getKey(),
        ]);
    }
}
