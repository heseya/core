<?php

namespace Tests\Feature;

use App\Enums\ValidationError;
use App\Listeners\WebHookEventListener;
use App\Models\WebHook;
use Domain\Metadata\Enums\MetadataType;
use Domain\Page\Events\PageCreated;
use Domain\Page\Events\PageDeleted;
use Domain\Page\Events\PageUpdated;
use Domain\Page\Page;
use Domain\Seo\Models\SeoMetadata;
use Illuminate\Events\CallQueuedListener;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Str;
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
     * @dataProvider booleanProvider
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
     * @dataProvider booleanProvider
     */
    public function testUpdateWithSeo(string $user, bool $boolean, bool $booleanValue): void
    {
        $this->{$user}->givePermissionTo('pages.edit');

        $seo = SeoMetadata::factory()->create();
        $this->page->seo()->save($seo);
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

    /**
     * @dataProvider authProvider
     */
    public function testReorderUnauthorized(string $user): void
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
    public function testReorder(string $user): void
    {
        $this->{$user}->givePermissionTo('pages.edit');

        DB::table('pages')->delete();
        $page = Page::factory()->count(3)->create();

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
