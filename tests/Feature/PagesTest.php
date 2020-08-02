<?php

namespace Tests\Feature;

use App\Models\Page;
use Laravel\Passport\Passport;
use Tests\TestCase;

class PagesTest extends TestCase
{
    private Page $page;
    private Page $page_hidden;

    private array $expected;
    private array $expected_view;

    public function setUp(): void
    {
        parent::setUp();

        $this->page = factory(Page::class)->create([
            'public' => true,
        ]);

        $this->page_hidden = factory(Page::class)->create([
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
            'content_md' => $this->page->content_md,
            'content_html' => parsedown($this->page->content_md),
        ]);
    }

    /**
     * @return void
     */
    public function testIndex()
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

    /**
     * @return void
     */
    public function testView()
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

    /**
     * @return void
     */
    public function testViewHidden()
    {
        $response = $this->getJson('/pages/' . $this->page_hidden->slug);
        $response->assertUnauthorized();

        $response = $this->getJson('/pages/id:' . $this->page_hidden->getKey());
        $response->assertUnauthorized();

        Passport::actingAs($this->user);

        $response = $this->getJson('/pages/' . $this->page_hidden->slug);
        $response->assertOk();

        $response = $this->getJson('/pages/id:' . $this->page_hidden->getKey());
        $response->assertOk();
    }

    /**
     * @return void
     */
    public function testCreate()
    {
        $response = $this->postJson('/pages');
        $response->assertUnauthorized();

        Passport::actingAs($this->user);

        $page = [
            'name' => 'Test',
            'slug' => 'test-test',
            'public' => true,
            'content_md' => '# hello world',
            'content_html' => '<h1>hello world</h1>',
        ];

        $response = $this->postJson('/pages', $page);
        $response
            ->assertJson(['data' => $page])
            ->assertCreated();
    }

    /**
     * @return void
     */
    public function testUpdate()
    {
        $response = $this->patchJson('/pages/id:' . $this->page->getKey());
        $response->assertUnauthorized();

        Passport::actingAs($this->user);

        $page = [
            'name' => 'Test 2',
            'slug' => 'test-2',
            'public' => false,
            'content_md' => '# hello world 2',
            'content_html' => '<h1>hello world 2</h1>',
        ];

        $response = $this->patchJson(
            '/pages/id:' . $this->page->getKey(),
            $page,
        );

        $response
            ->assertOk()
            ->assertJson(['data' => $page]);
    }

    /**
     * @return void
     */
    public function testDelete()
    {
        $response = $this->patchJson('/pages/id:' . $this->page->getKey());
        $response->assertUnauthorized();

        Passport::actingAs($this->user);

        $response = $this->deleteJson('/pages/id:' . $this->page->getKey());
        $response->assertNoContent();
    }
}
