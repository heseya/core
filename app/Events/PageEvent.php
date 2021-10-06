<?php

namespace App\Events;

use App\Models\Page;

abstract class PageEvent extends WebHookEvent
{
    private Page $page;

    public function __construct(Page $page)
    {
        $this->page = $page;
    }

    public function getData(): array
    {
        return $this->page->toArray();
    }

    public function isHidden(): bool
    {
        return $this->page->public;
    }
}
