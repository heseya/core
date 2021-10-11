<?php

namespace App\Events;

use App\Models\Page;
use Illuminate\Support\Str;

abstract class PageEvent extends WebHookEvent
{
    protected Page $page;

    public function __construct(Page $page)
    {
        parent::__construct();
        $this->page = $page;
    }

    public function getData(): array
    {
        return [
            'data' => $this->page->toArray(),
            'data_type' => Str::remove('App\\Models\\', $this->page::class),
            'event' => Str::remove('App\\Events\\', $this::class),
            'triggered_at' => $this->triggered_at,
        ];
    }

    public function isHidden(): bool
    {
        return !$this->page->public;
    }
}
