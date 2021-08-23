<?php

namespace Tests\Feature;

use App\Models\Page;
use App\Services\Contracts\MarkdownServiceContract;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Laravel\Passport\Passport;
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

    public function testIndex(): void
    {
        $response = $this->getJson('/pages');
        $response
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJson(['data' => [
                0 => $this->expected,
            ]]);

        Passport::actingAs($this->user);

        $response = $this->getJson('/pages');
        $response
            ->assertOk()
            ->assertJsonCount(2, 'data');
    }

    public function testView(): void
    {
        $response = $this->getJson('/pages/' . $this->page->slug);
        $response
            ->assertOk()
            ->assertJson(['data' => $this->expected_view]);

        $response = $this->getJson('/pages/id:' . $this->page->getKey());
        $response->assertUnauthorized();

        Passport::actingAs($this->user);

        $response = $this->getJson('/pages/id:' . $this->page->getKey());
        $response
            ->assertOk()
            ->assertJson(['data' => $this->expected_view]);
    }

    public function testViewHidden(): void
    {
        $response = $this->getJson('/pages/' . $this->page_hidden->slug);
        $response->assertNotFound();

        $response = $this->getJson('/pages/id:' . $this->page_hidden->getKey());
        $response->assertUnauthorized();

        Passport::actingAs($this->user);

        $response = $this->getJson('/pages/' . $this->page_hidden->slug);
        $response->assertOk();

        $response = $this->getJson('/pages/id:' . $this->page_hidden->getKey());
        $response->assertOk();
    }

    public function testCreateByOrder(): void
    {
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

            $uuid[] = $response->getData()->data->id;
        }

        $this->assertCount(3, $uuid);
        $this->assertDatabaseHas('pages', [
            'id' => $uuid[0],
            'order' => 1,
        ]);
        $this->assertDatabaseHas('pages', [
            'id' => $uuid[2],
            'order' => 3,
        ]);

        // change
        $response = $this->actingAs($this->user)->postJson('/pages/order', [
            'pages' => $uuid,
        ]);
        $response->assertNoContent();

        // check
        $response = $this->actingAs($this->user)->getJson('/pages/id:' . $uuid[0]);
        $response->assertOk();
        $this->assertDatabaseHas('pages', [
            'id' => $uuid[0],
            'order' => 0,
        ]);

        $response = $this->actingAs($this->user)->getJson('/pages/id:' . $uuid[2]);
        $response->assertOk();
        $this->assertDatabaseHas('pages', [
            'id' => $uuid[2],
            'order' => 2,
        ]);
    }

    public function testCreate(): void
    {
        $response = $this->postJson('/pages');
        $response->assertUnauthorized();

        Passport::actingAs($this->user);

        $html = '<h1>hello world</h1>';
        $page = [
            'name' => 'Test',
            'slug' => 'test-test',
            'public' => true,
            'content_html' => $html,
        ];

        $response = $this->postJson('/pages', $page);
        $response
            ->assertJson(['data' => $page + ['content_md' => $this->markdownService->fromHtml($html)]])
            ->assertCreated();

        $this->assertDatabaseHas('pages', $page);
    }

    public function testUpdate(): void
    {
        $response = $this->patchJson('/pages/id:' . $this->page->getKey());
        $response->assertUnauthorized();

        Passport::actingAs($this->user);

        $html = '<h1>hello world 2</h1>';
        $page = [
            'name' => 'Test 2',
            'slug' => 'test-2',
            'public' => false,
            'content_html' => $html,
        ];

        $response = $this->patchJson(
            '/pages/id:' . $this->page->getKey(),
            $page,
        );

        $response
            ->assertOk()
            ->assertJson(['data' => $page + ['content_md' => $this->markdownService->fromHtml($html)]]);

        $this->assertDatabaseHas('pages', $page + ['id' => $this->page->getKey()]);
    }

    public function testDelete(): void
    {
        $page = $this->page->toArray();
        unset($page['content_html']);

        $response = $this->patchJson('/pages/id:' . $this->page->getKey());
        $response->assertUnauthorized();
        $this->assertDatabaseHas('pages', $page);

        Passport::actingAs($this->user);

        $response = $this->deleteJson('/pages/id:' . $this->page->getKey());
        $response->assertNoContent();
        $this->assertDeleted($this->page);
    }

    public function testSortByOrder(): void
    {
        DB::table('pages')->delete();
        $page = Page::factory()->count(10)->create();

        $this->actingAs($this->user)->postJson('/pages/order', [
            'pages' => $page->pluck('id')->toArray(),
        ])->assertNoContent();

        $response = $this->getJson('/pages');
        $data = $response->getData()->data;
        $response
            ->assertOk()
            ->assertJsonCount(10, 'data')
            ->assertJsonFragment(
                [
                     'id' => $data[3]->id,
                     'name' => $data[3]->name,
                     'public' => $data[3]->public,
                     'order' => 3,
                     'slug' => $data[3]->slug,
                ],
            );

        $this->assertDatabaseHas('pages', [
            'id' => $data[3]->id,
            'order' => 3,
        ]);
        $this->assertDatabaseHas('pages', [
            'id' => $data[6]->id,
            'order' => 6,
        ]);
    }
}
