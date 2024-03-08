<?php

namespace Tests\Feature\Pages;

use App\Enums\ValidationError;
use App\Listeners\WebHookEventListener;
use App\Models\WebHook;
use Domain\Page\Events\PageCreated;
use Domain\Page\Events\PageDeleted;
use Illuminate\Events\CallQueuedListener;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Str;

class PageCreateTest extends PageTestCase
{
    public function testCreateUnauthorized(): void
    {
        Event::fake([PageCreated::class]);

        $response = $this->postJson('/pages');
        $response->assertForbidden();

        Event::assertNotDispatched(PageCreated::class);
    }

    /**
     * @dataProvider authProvider
     */
    public function testCreate(string $user): void
    {
        $this->{$user}->givePermissionTo('pages.add');

        Event::fake([PageCreated::class]);

        $html = '<h1>hello world</h1>';
        $this
            ->actingAs($this->{$user})
            ->postJson('/pages', [
                'translations' => [
                    $this->lang => [
                        'name' => 'Test',
                        'content_html' => $html,
                    ],
                ],
                'published' => [$this->lang],
                'slug' => 'test-test',
                'public' => true,
            ])
            ->assertCreated()
            ->assertJsonFragment([
                'name' => 'Test',
                'slug' => 'test-test',
                'public' => true,
                'content_html' => $html,
                'published' => [
                    $this->lang,
                ],
            ]);

        $this->assertDatabaseHas('pages', [
            "name->{$this->lang}" => 'Test',
            "content_html->{$this->lang}" => $html,
            'slug' => 'test-test',
            'public' => true,
        ]);

        Event::assertDispatched(PageCreated::class);
    }

    /**
     * @dataProvider authProvider
     */
    public function testCreateWithSameSlug(string $user): void
    {
        $this->{$user}->givePermissionTo('pages.add');

        $response = $this->actingAs($this->{$user})->postJson('/pages', [
            'translations' => [
                $this->lang => [
                    'name' => 'Test',
                    'content_html' => '<h1>html</h1>',
                ],
            ],
            'slug' => $this->page->slug,
            'public' => true,
        ]);

        $response->assertJsonFragment([
            'key' => ValidationError::UNIQUE,
        ]);
    }

    /**
     * @dataProvider authProvider
     */
    public function testCreateWithMetadata($user): void
    {
        $this->{$user}->givePermissionTo('pages.add');

        Event::fake([PageCreated::class]);

        $html = '<h1>hello world</h1>';
        $this
            ->actingAs($this->{$user})
            ->postJson('/pages', [
                'translations' => [
                    $this->lang => [
                        'name' => 'Test',
                        'content_html' => $html,
                    ],
                ],
                'slug' => 'test-test',
                'public' => true,
                'metadata' => [
                    'attributeMeta' => 'attributeValue',
                ],
            ])
            ->assertJson([
                'data' => [
                    'name' => 'Test',
                    'content_html' => $html,
                    'slug' => 'test-test',
                    'public' => true,
                    'metadata' => [
                        'attributeMeta' => 'attributeValue',
                    ],
                ],
            ])
            ->assertCreated();

        Event::assertDispatched(PageCreated::class);
    }

    /**
     * @dataProvider authProvider
     */
    public function testCreateWithMetadataPrivate(string $user): void
    {
        $this->{$user}->givePermissionTo(['pages.add', 'pages.show_metadata_private']);

        Event::fake([PageCreated::class]);

        $html = '<h1>hello world</h1>';
        $this
            ->actingAs($this->{$user})
            ->postJson('/pages', [
                'translations' => [
                    $this->lang => [
                        'name' => 'Test',
                        'content_html' => $html,
                    ],
                ],
                'slug' => 'test-test',
                'public' => true,
                'metadata_private' => [
                    'attributeMetaPriv' => 'attributeValue',
                ],
            ])
            ->assertJson([
                'data' => [
                    'name' => 'Test',
                    'content_html' => $html,
                    'slug' => 'test-test',
                    'public' => true,
                    'metadata_private' => [
                        'attributeMetaPriv' => 'attributeValue',
                    ],
                ],
            ])
            ->assertCreated();

        Event::assertDispatched(PageCreated::class);
    }

    /**
     * @dataProvider authProvider
     */
    public function testCreateWithWebHook(string $user): void
    {
        $this->{$user}->givePermissionTo('pages.add');

        $webHook = WebHook::factory()->create([
            'events' => [
                'PageCreated',
            ],
            'model_type' => $this->{$user}::class,
            'creator_id' => $this->{$user}->getKey(),
            'with_issuer' => true,
            'with_hidden' => false,
        ]);

        Bus::fake();

        $html = '<h1>hello world</h1>';
        $response = $this
            ->actingAs($this->{$user})
            ->postJson('/pages', [
                'translations' => [
                    $this->lang => [
                        'name' => 'Test',
                        'content_html' => $html,
                    ],
                ],
                'published' => [$this->lang],
                'slug' => 'test-test',
                'public' => true,
            ])
            ->assertJsonFragment([
                'name' => 'Test',
                'slug' => 'test-test',
                'public' => true,
                'content_html' => $html,
            ])
            ->assertCreated();

        $this->assertDatabaseHas('pages', [
            "name->{$this->lang}" => 'Test',
            "content_html->{$this->lang}" => $html,
            'slug' => 'test-test',
            'public' => true,
        ]);

        Bus::assertDispatched(CallQueuedListener::class, function ($job) {
            return $job->class === WebHookEventListener::class
                && $job->data[0] instanceof PageCreated;
        });
    }

    /**
     * @dataProvider authWithTwoBooleansProvider
     */
    public function testCreateWithSeo(string $user, bool $boolean, bool $booleanValue): void
    {
        $this->{$user}->givePermissionTo('pages.add');

        $html = '<h1>hello world</h1>';

        $this->actingAs($this->{$user})->json('POST', '/pages', [
            'translations' => [
                $this->lang => [
                    'name' => 'Test',
                    'content_html' => $html,
                ],
            ],
            'published' => [$this->lang],
            'slug' => 'test-test',
            'public' => true,
            'seo' => [
                'translations' => [
                    $this->lang => [
                        'title' => 'seo title',
                        'description' => 'seo description',
                        'no_index' => $booleanValue,
                    ],
                ],
                'published' => [$this->lang],
            ],
        ])->assertCreated();

        $this->assertDatabaseHas('pages', [
            "name->{$this->lang}" => 'Test',
            "content_html->{$this->lang}" => $html,
            'slug' => 'test-test',
            'public' => true,
        ]);
        $this->assertDatabaseHas('seo_metadata', [
            "title->{$this->lang}" => 'seo title',
            "description->{$this->lang}" => 'seo description',
            "no_index->{$this->lang}" => $booleanValue,
        ]);
    }

    /**
     * @dataProvider authProvider
     */
    public function testCreateByOrder(string $user): void
    {
        $this->{$user}->givePermissionTo('pages.add');

        Event::fake([PageCreated::class]);

        $uuids = [];

        for ($i = 0; $i < 3; ++$i) {
            $name = ' order test ' . $this->faker->sentence(mt_rand(1, 3));

            $response = $this->actingAs($this->{$user})->postJson('/pages', [
                'translations' => [
                    $this->lang => [
                        'name' => $name,
                        'content_html' => '<p>' . $this->faker->sentence(mt_rand(10, 30)) . '</p>',
                    ],
                ],
                'published' => [$this->lang],
                'slug' => Str::slug($name),
                'public' => $this->faker->boolean,
            ]);
            $response->assertCreated();

            $uuids[] = $response->json('data.id');
        }

        $this->assertDatabaseHas('pages', [
            'id' => $uuids[0],
            'order' => 1,
        ]);
        $this->assertDatabaseHas('pages', [
            'id' => $uuids[1],
            'order' => 2,
        ]);
        $this->assertDatabaseHas('pages', [
            'id' => $uuids[2],
            'order' => 3,
        ]);

        Event::assertDispatched(PageCreated::class);
    }

    /**
     * @dataProvider authProvider
     */
    public function testDeleteAndCreateWithTheSameSlug(string $user): void
    {
        $this->{$user}->givePermissionTo('pages.add');
        $this->{$user}->givePermissionTo('pages.remove');

        Event::fake([PageDeleted::class]);
        $this->page->slug = 'test';
        $this->page->save();

        $this->actingAs($this->{$user})
            ->deleteJson('/pages/id:' . $this->page->getKey())
            ->assertNoContent();
        $this->assertSoftDeleted($this->page);

        $this->page->refresh();

        $this->assertEquals('test_' . $this->page->deleted_at, $this->page->slug);

        Event::assertDispatched(PageDeleted::class);
        Event::fake([PageCreated::class]);

        $this
            ->actingAs($this->{$user})
            ->postJson('/pages', [
                'translations' => [
                    $this->lang => [
                        'name' => 'Test',
                        'content_html' => '<h1>hello world</h1>',
                    ],
                ],
                'published' => [$this->lang],
                'slug' => 'test',
                'public' => true,
            ])
            ->assertJsonFragment([
                'name' => 'Test',
                'content_html' => '<h1>hello world</h1>',
            ])
            ->assertCreated();

        $this->assertDatabaseHas('pages', [
            "name->{$this->lang}" => 'Test',
            "content_html->{$this->lang}" => '<h1>hello world</h1>',
        ]);

        Event::assertDispatched(PageCreated::class);
    }
}
