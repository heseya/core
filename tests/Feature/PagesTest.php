<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Page;
use Laravel\Passport\Passport;

class PagesTest extends TestCase
{
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
            'id' => $this->page->id,
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
        $response = $this->get('/pages');
        $response
            ->assertOk()
            ->assertJsonCount(1, 'data') // Shoud show only public pages.
            ->assertJson(['data' => [
                0 => $this->expected,
            ]]);

        Passport::actingAs($this->user);

        $response = $this->get('/pages');
        $response
            ->assertOk()
            ->assertJsonCount(2, 'data'); // Shoud show all pages.
    }

    /**
     * @return void
     */
    public function testView()
    {
        $response = $this->get('/pages/' . $this->page->slug);
        $response
            ->assertOk()
            ->assertJson(['data' => $this->expected_view]);

        $response = $this->get('/pages/id:' . $this->page->id);
        $response->assertUnauthorized();

        Passport::actingAs($this->user);

        $response = $this->get('/pages/id:' . $this->page->id);
        $response
            ->assertOk()
            ->assertJson(['data' => $this->expected_view]);
    }

    /**
     * @return void
     */
    public function testViewHidden()
    {
        $response = $this->get('/pages/' . $this->page_hidden->slug);
        $response->assertUnauthorized();

        $response = $this->get('/pages/id:' . $this->page_hidden->id);
        $response->assertUnauthorized();

        Passport::actingAs($this->user);

        $response = $this->get('/pages/' . $this->page_hidden->slug);
        $response->assertOk();

        $response = $this->get('/pages/id:' . $this->page_hidden->id);
        $response->assertOk();
    }

    /**
     * @return void
     */
    public function testCreate()
    {
        $response = $this->post('/pages');
        $response->assertUnauthorized();

        Passport::actingAs($this->user);

        $page = [
            'name' => 'Test',
            'slug' => 'test-test',
            'public' => true,
            'content_md' => '# hello world',
            'content_html' => '<h1>hello world</h1>',
        ];

        $response = $this->post('/pages', $page);
        $response
            ->assertJson(['data' => $page])
            ->assertCreated();
    }

    /**
     * @return void
     */
    public function testUpdate()
    {
        $response = $this->patch('/pages/id:' . $this->page->id);
        $response->assertUnauthorized();

        Passport::actingAs($this->user);

        $page = [
            'name' => 'Test 2',
            'slug' => 'test-2',
            'public' => false,
            'content_md' => '# hello world 2',
            'content_html' => '<h1>hello world 2</h1>',
        ];

        $response = $this->patch(
            '/pages/id:' . $this->page->id,
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
        $response = $this->patch('/pages/id:' . $this->page->id);
        $response->assertUnauthorized();

        Passport::actingAs($this->user);

        $response = $this->delete('/pages/id:' . $this->page->id);
        $response->assertNoContent();
    }
}
