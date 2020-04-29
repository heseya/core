<?php

namespace Tests\Feature;

use App\Page;
use Tests\TestCase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;

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
            'content_md' => $this->page->content_md,
            'content_html' => parsedown($this->page->content_md),
        ];
    }

    /**
     * @return void
     */
    public function testIndex()
    {
        $response = $this->get('/pages');

        $response
            ->assertStatus(200)
            ->assertJsonCount(1, 'data') // Shoud show only public pages.
            ->assertJson(['data' => [
                0 => $this->expected,
            ]]);
    }

    /**
     * @return void
     */
    public function testView()
    {
        $response = $this->get('/pages/' . $this->page->slug);

        $response
            ->assertStatus(200)
            ->assertExactJson(['data' => $this->expected]);
    }

    /**
     * @return void
     */
    public function testViewHidden()
    {
        $response = $this->get('/pages/' . $this->page_hidden->slug);

        $response
            ->assertStatus(401)
            ->assertJsonStructure(['error' => [
                'code',
                'message',
            ]]);
    }

    /**
     * @return void
     */
    public function testCreate()
    {
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
        ->assertStatus(201);
    }

    /**
     * @return void
     */
    public function testUpdate()
    {
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
            ->assertStatus(200)
            ->assertJson(['data' => $page]);
    }

    /**
     * @return void
     */
    public function testDelete()
    {
        $response = $this->delete('/pages/id:' . $this->page->id);

        $response->assertStatus(204);
    }
}
