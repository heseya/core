<?php

namespace Tests\Feature;

use App\Enums\MetadataType;
use App\Enums\ValidationError;
use App\Events\PageCreated;
use App\Events\PageDeleted;
use App\Events\PageUpdated;
use App\Listeners\WebHookEventListener;
use App\Models\Page;
use App\Models\SeoMetadata;
use App\Models\WebHook;
use Illuminate\Events\CallQueuedListener;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Str;
use Spatie\WebhookServer\CallWebhookJob;
use Tests\TestCase;

class PageTest extends TestCase
{
    use WithFaker;

    private Page $page;
    private Page $page_hidden;

    private array $expected;
    private array $expected_view;

    public function setUp(): void
    {
        parent::setUp();

        $this->page = Page::factory()->create([
            'public' => true,
        ]);

        $this->page_hidden = Page::factory()->create([
            'public' => false,
        ]);

        $metadata = $this->page->metadata()->create([
            'name' => 'Metadata',
            'value' => 'metadata test',
            'value_type' => MetadataType::STRING,
            'public' => true,
        ]);

        // Expected response
        $this->expected = [
            'id' => $this->page->getKey(),
            'name' => $this->page->name,
            'slug' => $this->page->slug,
            'public' => $this->page->public,
            'metadata' => [],
        ];

        $this->expected_view = array_merge($this->expected, [
            'content_html' => $this->page->content_html,
            'metadata' => [
                $metadata->name => $metadata->value,
            ],
        ]);
    }

    public function testIndexUnauthorized(): void
    {
        $response = $this->getJson('/pages');
        $response->assertForbidden();
    }

    /**
     * @dataProvider authProvider
     */
    public function testIndex($user): void
    {
        $this->{$user}->givePermissionTo('pages.show');

        $this
            ->actingAs($this->{$user})
            ->getJson('/pages')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJson(['data' => [
                0 => $this->expected,
            ],
            ]);

        $this->assertQueryCountLessThan(11);
    }

    /**
     * @dataProvider authProvider
     */
    public function testIndexWithTranslationsFlag($user): void
    {
        $this->{$user}->givePermissionTo('pages.show');

        $response = $this
            ->actingAs($this->{$user})
            ->getJson('/pages?translations');

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
    public function testIndexWithTranslationsFlag2($user): void
    {
        $this->{$user}->givePermissionTo('pages.show');

        $response = $this
            ->actingAs($this->{$user})
            ->getJson('/pages?translations');

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
    public function testIndexByIds($user): void
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
            ->assertJson(['data' => [
                0 => $this->expected,
            ],
            ]);

        $this->assertQueryCountLessThan(11);
    }

    /**
     * @dataProvider authProvider
     */
    public function testIndexPerformance($user): void
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
    public function testIndexHidden($user): void
    {
        $this->{$user}->givePermissionTo(['pages.show', 'pages.show_hidden']);

        $response = $this->actingAs($this->{$user})->getJson('/pages');
        $response
            ->assertOk()
            ->assertJsonCount(2, 'data');
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
    public function testView($user): void
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
    public function testViewWrongIdOrSlug($user): void
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
    public function testViewPrivateMetadata($user): void
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
            ->assertJson(['data' => $this->expected_view +
                ['metadata_private' => [
                    $privateMetadata->name => $privateMetadata->value,
                ],
                ],
            ]);
    }

    /**
     * @dataProvider authProvider
     */
    public function testViewHiddenUnauthorized($user): void
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
    public function testViewHidden($user): void
    {
        $this->{$user}->givePermissionTo(['pages.show_details', 'pages.show_hidden']);

        $response = $this->actingAs($this->{$user})
            ->getJson('/pages/' . $this->page_hidden->slug);
        $response->assertOk();

        $response = $this->actingAs($this->{$user})
            ->getJson('/pages/id:' . $this->page_hidden->getKey());
        $response->assertOk();
    }

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
    public function testCreate($user): void
    {
        $this->{$user}->givePermissionTo('pages.add');

        Event::fake([PageCreated::class]);

        $html = '<h1>hello world</h1>';

        $this->actingAs($this->{$user})
            ->postJson('/pages', [
                'slug' => 'test-test',
                'public' => true,
                'translations' => [
                    $this->lang => [
                        'name' => 'Test',
                        'content_html' => $html,
                    ],
                ],
                'published' => [$this->lang],
            ])
            ->assertJson([
                'data' => [
                    'name' => 'Test',
                    'slug' => 'test-test',
                    'public' => true,
                    'content_html' => $html,
                ],
            ])
            ->assertCreated();

        $this->assertDatabaseHas('pages', [
            "name->{$this->lang}" => 'Test',
            'slug' => 'test-test',
            'public' => true,
            "content_html->{$this->lang}" => $html,
        ]);

        $response = $this->actingAs($this->{$user})
            ->postJson('/pages', [
                'slug' => 'test-test',
                'public' => true,
                'translations' => [
                    $this->lang => [
                        'name' => 'Test',
                        'content_html' => $html,
                    ],
                ],
                'published' => [$this->lang],
            ])
            ->assertJson([
                'data' => [
                    'name' => 'Test',
                    'slug' => 'test-test',
                    'public' => true,
                    'content_html' => $html,
                ],
            ])
            ->assertCreated();

        $this->assertDatabaseHas('pages', [
            "name->{$this->lang}" => 'Test',
            'slug' => 'test-test',
            'public' => true,
            "content_html->{$this->lang}" => $html,
        ]);

        Event::assertDispatched(PageCreated::class);
    }

    /**
     * @dataProvider authProvider
     */
    public function testCreateWithSameSlug($user): void
    {
        $this->{$user}->givePermissionTo('pages.add');

        $html = '<h1>hello world</h1>';
        $page = [
            'name' => 'Test',
            'slug' => $this->page->slug,
            'public' => true,
            'content_html' => $html,
        ];

        $response = $this->actingAs($this->{$user})->postJson('/pages', $page);

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
        $page = [
            'name' => 'Test',
            'slug' => 'test-test',
            'public' => true,
            'content_html' => $html,
            'metadata' => [
                'attributeMeta' => 'attributeValue',
            ],
        ];

        $this
            ->actingAs($this->{$user})
            ->postJson('/pages', $page)
            ->assertJson([
                'data' => $page,
            ])
            ->assertCreated();

        Event::assertDispatched(PageCreated::class);
    }

    /**
     * @dataProvider authProvider
     */
    public function testCreateWithMetadataPrivate($user): void
    {
        $this->{$user}->givePermissionTo(['pages.add', 'pages.show_metadata_private']);

        Event::fake([PageCreated::class]);

        $html = '<h1>hello world</h1>';
        $page = [
            'name' => 'Test',
            'slug' => 'test-test',
            'public' => true,
            'content_html' => $html,
            'metadata_private' => [
                'attributeMetaPriv' => 'attributeValue',
            ],
        ];

        $this
            ->actingAs($this->{$user})
            ->postJson('/pages', $page)
            ->assertJson([
                'data' => $page,
            ])
            ->assertCreated();

        Event::assertDispatched(PageCreated::class);
    }

    /**
     * @dataProvider authProvider
     */
    public function testCreateWithWebHook($user): void
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

        $response = $this->actingAs($this->{$user})
            ->postJson('/pages', [
                'slug' => 'test-test',
                'public' => true,
                'translations' => [
                    $this->lang => [
                        'name' => 'Test',
                        'content_html' => $html,
                    ],
                ],
                'published' => [$this->lang],
            ])
            ->assertJson([
                'data' => [
                    'name' => 'Test',
                    'slug' => 'test-test',
                    'public' => true,
                    'content_html' => $html,
                ],
            ])
            ->assertCreated();

        $this->assertDatabaseHas('pages', [
            "name->{$this->lang}" => 'Test',
            'slug' => 'test-test',
            'public' => true,
            "content_html->{$this->lang}" => $html,
        ]);

        $response = $this->actingAs($this->{$user})
            ->postJson('/pages', [
                'slug' => 'test-test',
                'public' => true,
                'translations' => [
                    $this->lang => [
                        'name' => 'Test',
                        'content_html' => $html,
                    ],
                ],
                'published' => [$this->lang],
            ])
            ->assertJson([
                'data' => [
                    'name' => 'Test',
                    'slug' => 'test-test',
                    'public' => true,
                    'content_html' => $html,
                ],
            ])
            ->assertCreated();

        $this->assertDatabaseHas('pages', [
            "name->{$this->lang}" => 'Test',
            'slug' => 'test-test',
            'public' => true,
            "content_html->{$this->lang}" => $html,
        ]);

        Bus::assertDispatched(CallQueuedListener::class, function ($job) {
            return $job->class === WebHookEventListener::class
                && $job->data[0] instanceof PageCreated;
        });

        $page = Page::find($response->getData()->data->id);

        $event = new PageCreated($page);
        $listener = new WebHookEventListener();
        $listener->handle($event);

        Bus::assertDispatched(CallWebhookJob::class, function ($job) use ($webHook, $page) {
            $payload = $job->payload;

            return $job->webhookUrl === $webHook->url
                && isset($job->headers['Signature'])
                && $payload['data']['id'] === $page->getKey()
                && $payload['data_type'] === 'Page'
                && $payload['event'] === 'PageCreated';
        });
    }

    /**
     * @dataProvider booleanProvider
     */
    public function testCreateWithSeo($user, $boolean, $booleanValue): void
    {
        $this->{$user}->givePermissionTo('pages.add');

        $html = '<h1>hello world</h1>';

        $this->actingAs($this->{$user})->json('POST', '/pages', ['data' => [
            'name' => 'Test',
            'slug' => 'test-test',
            'public' => true,
            'content_html' => $html,
            'seo' => [
                'title' => 'seo title',
                'description' => 'seo description',
            ],
        ]])->assertCreated();

        $this->assertDatabaseHas('pages', [
            "name->{$this->lang}" => 'Test',
            'slug' => 'test-test',
            'public' => true,
            "content_html->{$this->lang}" => $html,
        ]);
        $this->assertDatabaseHas('seo_metadata', [
            "title->{$this->lang}" => 'seo title',
            "description->{$this->lang}" => 'seo description',
            'no_index' => $booleanValue,
        ]);
    }

    /**
     * @dataProvider authProvider
     */
    public function testCreateByOrder($user): void
    {
        $this->{$user}->givePermissionTo('pages.add');

        Event::fake([PageCreated::class]);

        $uuids = [];

        for ($i = 0; $i < 3; ++$i) {
            $name = ' order test ' . $this->faker->sentence(mt_rand(1, 3));
            $page = [
                'name' => $name,
                'slug' => Str::slug($name),
                'public' => $this->faker->boolean,
                'content_html' => '<p>' . $this->faker->sentence(mt_rand(10, 30)) . '</p>',
            ];

            $response = $this->actingAs($this->{$user})->postJson('/pages', $page);
            $response->assertCreated();

            $uuids[] = $response->getData()->data->id;
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
    public function testDeleteAndCreateWithTheSameSlug($user): void
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
        $page = [
            'name' => 'Test',
            'slug' => 'test',
            'public' => true,
            'content_html' => '<h1>hello world</h1>',
        ];

        $response = $this->actingAs($this->{$user})->postJson('/pages', $page);
        $response->assertJson([
            'data' => $page,
        ])->assertCreated();

        $this->assertDatabaseHas('pages', $page);

        Event::assertDispatched(PageCreated::class);
    }

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

        $page = Page::find($response->getData()->data->id);

        $event = new PageUpdated($page);
        $listener = new WebHookEventListener();
        $listener->handle($event);

        Bus::assertDispatched(CallWebhookJob::class, function ($job) use ($webHook, $page) {
            $payload = $job->payload;

            return $job->webhookUrl === $webHook->url
                && isset($job->headers['Signature'])
                && $payload['data']['id'] === $page->getKey()
                && $payload['data_type'] === 'Page'
                && $payload['event'] === 'PageUpdated';
        });
        $this->assertDatabaseHas('pages', [
            'id' => $this->page->getKey(),
            "name->{$this->lang}" => 'Test 2',
            'slug' => 'test-2',
            'public' => false,
            "content_html->{$this->lang}" => $html,
        ]);
    }

    /**
     * @dataProvider booleanProvider
     */
    public function testUpdateWithSeo($user, $boolean, $booleanValue): void
    {
        $this->{$user}->givePermissionTo('pages.edit');

        $html = '<h1>hello world 2</h1>';
        $page = [
            'name' => 'Test 2',
            'slug' => 'test-2',
            'public' => $boolean,
            'content_html' => $html,
            'seo' => [
                'title' => 'seo title',
                'description' => 'seo description',
                'no_index' => $boolean,
            ],
        ];

        $seo = SeoMetadata::factory()->create();
        $this->page->seo()->save($seo);

        $response = $this->actingAs($this->{$user})->json(
            'PATCH',
            '/pages/id:' . $this->page->getKey(),
            $page
        );

        $response
            ->assertOk()
            ->assertJsonFragment([
                'title' => $page['seo']['title'],
                'description' => $page['seo']['description'],
                'no_index' => $booleanValue,
            ])->assertJsonFragment([
                'name' => $page['name'],
                'slug' => $page['slug'],
                'public' => $booleanValue,
                'content_html' => $html,
            ]);

        $this->assertDatabaseHas('pages', [
            'id' => $this->page->getKey(),
            "name->{$this->lang}" => 'Test 2',
            'slug' => 'test-2',
            'public' => $booleanValue,
            'content_html' => $html,
        ]);
        $this->assertDatabaseHas('seo_metadata', [
            'title' => 'seo title',
            'description' => 'seo description',
            'no_index' => $booleanValue,
        ]);
    }

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
    public function testDelete($user): void
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

    /**
     * @dataProvider authProvider
     */
    public function testReorderUnauthorized($user): void
    {
        DB::table('pages')->delete();
        $page = Page::factory()->count(10)->create();

        $this->actingAs($this->{$user})->postJson('/pages/reorder', [
            'pages' => $page->pluck('id')->toArray(),
        ])->assertForbidden();
    }

    /**
     * @dataProvider authProvider
     */
    public function testReorder($user): void
    {
        $this->{$user}->givePermissionTo('pages.edit');

        DB::table('pages')->delete();
        $page = Page::factory()->count(10)->create();

        $ids = $page->pluck('id');

        $this->actingAs($this->{$user})->postJson('/pages/reorder', [
            'pages' => $ids->toArray(),
        ])->assertNoContent();

        $ids->each(fn ($id, $order) => $this->assertDatabaseHas('pages', [
            'id' => $id,
            'order' => $order,
        ]));
    }
}
