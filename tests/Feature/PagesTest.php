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
            'content' => $this->page->content,
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
}
