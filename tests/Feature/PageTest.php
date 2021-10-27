<?php

namespace Tests\Feature;

use App\Events\PageCreated;
use App\Events\PageDeleted;
use App\Events\PageUpdated;
use App\Http\Resources\PageResource;
use App\Listeners\WebHookEventListener;
use App\Models\Page;
use App\Models\WebHook;
use App\Services\Contracts\MarkdownServiceContract;
use Illuminate\Events\CallQueuedListener;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Str;
use Laravel\Passport\Passport;
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

        $this->markdownService = app(MarkdownServiceContract::class);

        $this->page = Page::factory()->create([
            'public' => true,
        ]);

        $this->page_hidden = Page::factory()->create([
            'public' => false,
        ]);

        /**
         * Expected response
         */
        $this->expected = [
            'id' => $this->page->getKey(),
            'name' => $this->page->name,
            'slug' => $this->page->slug,
            'public' => $this->page->public,
        ];

        $this->expected_view = array_merge($this->expected, [
            'content_html' => $this->page->content_html,
            'content_md' => $this->markdownService->fromHtml($this->page->content_html),
        ]);
    }

    public function testIndexUnauthorized(): void
    {
        $response = $this->getJson('/pages');
        $response->assertForbidden();
    }

    public function testIndex(): void
    {
        $this->user->givePermissionTo('pages.show');

        $response = $this->actingAs($this->user)->getJson('/pages');
        $response
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJson(['data' => [
                0 => $this->expected,
            ]]);
    }

    public function testIndexHidden(): void
    {
        $this->user->givePermissionTo(['pages.show', 'pages.show_hidden']);

        $response = $this->actingAs($this->user)->getJson('/pages');
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

    public function testView(): void
    {
        $this->user->givePermissionTo('pages.show_details');

        $response = $this->actingAs($this->user)
            ->getJson('/pages/' . $this->page->slug);
        $response
            ->assertOk()
            ->assertJson(['data' => $this->expected_view]);

        $response = $this->actingAs($this->user)
            ->getJson('/pages/id:' . $this->page->getKey());
        $response
            ->assertOk()
            ->assertJson(['data' => $this->expected_view]);
    }

    public function testViewHiddenUnauthorized(): void
    {
        $this->user->givePermissionTo('pages.show_details');

        $response = $this->actingAs($this->user)
            ->getJson('/pages/' . $this->page_hidden->slug);
        $response->assertNotFound();

        $response = $this->actingAs($this->user)
            ->getJson('/pages/id:' . $this->page_hidden->getKey());
        $response->assertNotFound();
    }

    public function testViewHidden(): void
    {
        $this->user->givePermissionTo(['pages.show_details', 'pages.show_hidden']);

        $response = $this->actingAs($this->user)
            ->getJson('/pages/' . $this->page_hidden->slug);
        $response->assertOk();

        $response = $this->actingAs($this->user)
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

    public function testCreate(): void
    {
        $this->user->givePermissionTo('pages.add');

        Event::fake([PageCreated::class]);

        $html = '<h1>hello world</h1>';
        $page = [
            'name' => 'Test',
            'slug' => 'test-test',
            'public' => true,
            'content_html' => $html,
        ];

        $response = $this->actingAs($this->user)->postJson('/pages', $page);
        $response->assertJson([
            'data' => $page + [
                'content_md' => $this->markdownService->fromHtml($html),
            ],
        ])->assertCreated();

        $this->assertDatabaseHas('pages', $page);

        Event::assertDispatched(PageCreated::class);
    }

    public function testCreateWithWebHook(): void
    {
        $this->user->givePermissionTo('pages.add');

        $webHook = WebHook::factory()->create([
            'events' => [
                'PageCreated'
            ],
            'model_type' => $this->user::class,
            'creator_id' => $this->user->getKey(),
            'with_issuer' => true,
            'with_hidden' => false,
        ]);

        Bus::fake();

        $html = '<h1>hello world</h1>';
        $page = [
            'name' => 'Test',
            'slug' => 'test-test',
            'public' => true,
            'content_html' => $html,
        ];

        $response = $this->actingAs($this->user)->postJson('/pages', $page);
        $response->assertJson([
            'data' => $page + [
                    'content_md' => $this->markdownService->fromHtml($html),
                ],
        ])->assertCreated();

        $this->assertDatabaseHas('pages', $page);

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

    public function testCreateByOrder(): void
    {
        $this->user->givePermissionTo('pages.add');

        Event::fake([PageCreated::class]);

        for ($i = 0; $i < 3; $i++) {
            $name = ' order test ' . $this->faker->sentence(rand(1, 3));
            $page = [
                'name' => $name,
                'slug' => Str::slug($name),
                'public' => $this->faker->boolean,
                'content_html' => '<p>' . $this->faker->sentence(rand(10, 30)) . '</p>',
            ];

            $response = $this->actingAs($this->user)->postJson('/pages', $page);
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

    public function testUpdateUnauthorized(): void
    {
        Event::fake(PageUpdated::class);

        $response = $this->patchJson('/pages/id:' . $this->page->getKey());
        $response->assertForbidden();

        Event::assertNotDispatched(PageUpdated::class);
    }

    public function testUpdate(): void
    {
        $this->user->givePermissionTo('pages.edit');

        Event::fake(PageUpdated::class);

        $html = '<h1>hello world 2</h1>';
        $page = [
            'name' => 'Test 2',
            'slug' => 'test-2',
            'public' => false,
            'content_html' => $html,
        ];

        $response = $this->actingAs($this->user)->patchJson(
            '/pages/id:' . $this->page->getKey(),
            $page,
        );

        $response
            ->assertOk()
            ->assertJson(['data' => $page + ['content_md' => $this->markdownService->fromHtml($html)]]);

        $this->assertDatabaseHas('pages', $page + ['id' => $this->page->getKey()]);

        Event::assertDispatched(PageUpdated::class);
    }

    public function testUpdateWithWebHook(): void
    {
        $this->user->givePermissionTo('pages.edit');

        $webHook = WebHook::factory()->create([
            'events' => [
                'PageUpdated'
            ],
            'model_type' => $this->user::class,
            'creator_id' => $this->user->getKey(),
            'with_issuer' => true,
            'with_hidden' => true,
        ]);

        Bus::fake();

        $html = '<h1>hello world 2</h1>';
        $page = [
            'name' => 'Test 2',
            'slug' => 'test-2',
            'public' => false,
            'content_html' => $html,
        ];

        $response = $this->actingAs($this->user)->patchJson(
            '/pages/id:' . $this->page->getKey(),
            $page,
        );

        $response
            ->assertOk()
            ->assertJson(['data' => $page + ['content_md' => $this->markdownService->fromHtml($html)]]);

        $this->assertDatabaseHas('pages', $page + ['id' => $this->page->getKey()]);

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
    }

    public function testDeleteUnauthorized(): void
    {
        Event::fake(PageDeleted::class);

        $page = $this->page->toArray();
        unset($page['content_html']);

        $response = $this->patchJson('/pages/id:' . $this->page->getKey());
        $response->assertForbidden();
        $this->assertDatabaseHas('pages', $page);

        Event::assertNotDispatched(PageDeleted::class);
    }

    public function testDelete(): void
    {
        $this->user->givePermissionTo('pages.remove');

        Event::fake([PageDeleted::class]);

        $response = $this->actingAs($this->user)
            ->deleteJson('/pages/id:' . $this->page->getKey());
        $response->assertNoContent();
        $this->assertDeleted($this->page);

        Event::assertDispatched(PageDeleted::class);
    }

    public function testDeleteWithWebHook(): void
    {
        $this->user->givePermissionTo('pages.remove');

        $webHook = WebHook::factory()->create([
            'events' => [
                'PageDeleted'
            ],
            'model_type' => $this->user::class,
            'creator_id' => $this->user->getKey(),
            'with_issuer' => true,
            'with_hidden' => true,
        ]);

        Bus::fake();

        $response = $this->actingAs($this->user)
            ->deleteJson('/pages/id:' . $this->page->getKey());
        $response->assertNoContent();
        $this->assertDeleted($this->page);

        Bus::assertDispatched(CallQueuedListener::class, function ($job) {
            return $job->class === WebHookEventListener::class
                && $job->data[0] instanceof PageDeleted;
        });

        $page = $this->page;

        $event = new PageDeleted(PageResource::make($page)->resolve(), $page::class);
        $listener = new WebHookEventListener();
        $listener->handle($event);

        Bus::assertDispatched(CallWebhookJob::class, function ($job) use ($webHook, $page) {
            $payload = $job->payload;
            return $job->webhookUrl === $webHook->url
                && isset($job->headers['Signature'])
                && $payload['data']['id'] === $page->getKey()
                && $payload['data_type'] === 'Page'
                && $payload['event'] === 'PageDeleted';
        });
    }

    public function testReorderUnauthorized(): void
    {
        DB::table('pages')->delete();
        $page = Page::factory()->count(10)->create();

        $this->actingAs($this->user)->postJson('/pages/reorder', [
            'pages' => $page->pluck('id')->toArray(),
        ])->assertForbidden();
    }

    public function testReorder(): void
    {
        $this->user->givePermissionTo('pages.edit');

        DB::table('pages')->delete();
        $page = Page::factory()->count(10)->create();

        $ids = $page->pluck('id');

        $this->actingAs($this->user)->postJson('/pages/reorder', [
            'pages' => $ids->toArray(),
        ])->assertNoContent();

        $ids->each(fn ($id, $order) => $this->assertDatabaseHas('pages', [
            'id' => $id,
            'order' => $order,
        ]));
    }
}
