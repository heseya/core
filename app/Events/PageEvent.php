<?php

namespace App\Events;

use App\Http\Resources\PageResource;
use App\Models\Page;

abstract class PageEvent extends WebHookEvent
{
    protected Page $page;

    public function __construct(Page $page)
    {
        parent::__construct();
        $this->page = $page;
    }

    public function isHidden(): bool
    {
        return !$this->page->public;
    }

    public function getDataContent(): array
    {
        return PageResource::make($this->page)->resolve();
    }

    public function getDataType(): string
    {
        return $this->getModelClass($this->page);
    }
}
