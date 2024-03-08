<?php

namespace Tests\Feature\Pages;

use Domain\Metadata\Enums\MetadataType;
use Domain\Page\Page;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

abstract class PageTestCase extends TestCase
{
    use WithFaker;

    protected Page $page;
    private Page $page_hidden;

    protected array $expected;
    private array $expected_view;

    public function setUp(): void
    {
        parent::setUp();

        $this->page = Page::factory()->create([
            'public' => true,
        ]);

        $this->page->metadata()->create([
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
    }
}
