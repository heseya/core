<?php

declare(strict_types=1);

namespace Domain\Page\Events;

use App\Events\WebHookEvent;
use Domain\Page\Page;
use Domain\Page\PageResource;

abstract class PageEvent extends WebHookEvent
{
    public function __construct(
        protected Page $page,
    ) {
        parent::__construct();
    }

    public function isHidden(): bool
    {
        return !$this->page->public;
    }

    /**
     * @return array<string, string>
     */
    public function getDataContent(): array
    {
        return PageResource::make($this->page)->resolve();
    }

    public function getDataType(): string
    {
        return $this->getModelClass($this->page);
    }
}
