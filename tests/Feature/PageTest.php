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
        $this->$user->givePermissionTo('pages.show');

        $response = $this->actingAs($this->$user)->getJson('/pages');
        $response
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJson(['data' => [
                0 => $this->expected,
            ]]);
    }

    /**
     * @dataProvider authProvider
     */
    public function testIndexHidden($user): void
    {
        $this->$user->givePermissionTo(['pages.show', 'pages.show_hidden']);

        $response = $this->actingAs($this->$user)->getJson('/pages');
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
        $this->$user->givePermissionTo('pages.show_details');

        $response = $this->actingAs($this->$user)
            ->getJson('/pages/' . $this->page->slug);
        $response
            ->assertOk()
            ->assertJson(['data' => $this->expected_view]);

        $response = $this->actingAs($this->$user)
            ->getJson('/pages/id:' . $this->page->getKey());
        $response
            ->assertOk()
            ->assertJson(['data' => $this->expected_view]);
    }

    /**
     * @dataProvider authProvider
     */
    public function testViewHiddenUnauthorized($user): void
    {
        $this->$user->givePermissionTo('pages.show_details');

        $response = $this->actingAs($this->$user)
            ->getJson('/pages/' . $this->page_hidden->slug);
        $response->assertNotFound();

        $response = $this->actingAs($this->$user)
            ->getJson('/pages/id:' . $this->page_hidden->getKey());
        $response->assertNotFound();
    }

    /**
     * @dataProvider authProvider
     */
    public function testViewHidden($user): void
    {
        $this->$user->givePermissionTo(['pages.show_details', 'pages.show_hidden']);

        $response = $this->actingAs($this->$user)
            ->getJson('/pages/' . $this->page_hidden->slug);
        $response->assertOk();

        $response = $this->actingAs($this->$user)
            ->getJson('/pages/id:' . $this->page_hidden->getKey());
        $response->assertOk();
    }

    public function testCreateUnauthorized(): void
    {
        $response = $this->postJson('/pages');
        $response->assertForbidden();
    }

    /**
     * @dataProvider authProvider
     */
    public function testCreate($user): void
    {
        $this->$user->givePermissionTo('pages.add');

        $html = '<h1>hello world</h1>';
        $page = [
            'name' => 'Test',
            'slug' => 'test-test',
            'public' => true,
            'content_html' => $html,
        ];

        $response = $this->actingAs($this->$user)->postJson('/pages', $page);
        $response->assertJson([
            'data' => $page + [
                'content_md' => $this->markdownService->fromHtml($html),
            ],
        ])->assertCreated();

        $this->assertDatabaseHas('pages', $page);
    }

    /**
     * @dataProvider authProvider
     */
    public function testCreateByOrder($user): void
    {
        $this->$user->givePermissionTo('pages.add');

        for ($i = 0; $i < 3; $i++) {
            $name = ' order test ' . $this->faker->sentence(rand(1, 3));
            $page = [
                'name' => $name,
                'slug' => Str::slug($name),
                'public' => $this->faker->boolean,
                'content_html' => '<p>' . $this->faker->sentence(rand(10, 30)) . '</p>',
            ];

            $response = $this->actingAs($this->$user)->postJson('/pages', $page);
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
    }

    public function testUpdateUnauthorized(): void
    {
        $response = $this->patchJson('/pages/id:' . $this->page->getKey());
        $response->assertForbidden();
    }

    /**
     * @dataProvider authProvider
     */
    public function testUpdate($user): void
    {
        $this->$user->givePermissionTo('pages.edit');

        $html = '<h1>hello world 2</h1>';
        $page = [
            'name' => 'Test 2',
            'slug' => 'test-2',
            'public' => false,
            'content_html' => $html,
        ];

        $response = $this->actingAs($this->$user)->patchJson(
            '/pages/id:' . $this->page->getKey(),
            $page,
        );

        $response
            ->assertOk()
            ->assertJson(['data' => $page + ['content_md' => $this->markdownService->fromHtml($html)]]);

        $this->assertDatabaseHas('pages', $page + ['id' => $this->page->getKey()]);
    }

    public function testDeleteUnauthorized(): void
    {
        $page = $this->page->toArray();
        unset($page['content_html']);

        $response = $this->patchJson('/pages/id:' . $this->page->getKey());
        $response->assertForbidden();
        $this->assertDatabaseHas('pages', $page);
    }

    /**
     * @dataProvider authProvider
     */
    public function testDelete($user): void
    {
        $this->$user->givePermissionTo('pages.remove');

        $response = $this->actingAs($this->$user)
            ->deleteJson('/pages/id:' . $this->page->getKey());
        $response->assertNoContent();
        $this->assertDeleted($this->page);
    }

    /**
     * @dataProvider authProvider
     */
    public function testReorderUnauthorized($user): void
    {
        DB::table('pages')->delete();
        $page = Page::factory()->count(10)->create();

        $this->actingAs($this->$user)->postJson('/pages/reorder', [
            'pages' => $page->pluck('id')->toArray(),
        ])->assertForbidden();
    }

    /**
     * @dataProvider authProvider
     */
    public function testReorder($user): void
    {
        $this->$user->givePermissionTo('pages.edit');

        DB::table('pages')->delete();
        $page = Page::factory()->count(10)->create();

        $ids = $page->pluck('id');

        $this->actingAs($this->$user)->postJson('/pages/reorder', [
            'pages' => $ids->toArray(),
        ])->assertNoContent();

        $ids->each(fn ($id, $order) => $this->assertDatabaseHas('pages', [
            'id' => $id,
            'order' => $order,
        ]));
    }
}
