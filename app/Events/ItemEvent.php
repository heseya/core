<?php

namespace App\Events;

use App\Models\Item;

abstract class ItemEvent extends WebHookEvent
{
    protected Item $item;

    public function __construct(Item $item)
    {
        $this->item = $item;
    }

    public function getData(): array
    {
        return $this->item->toArray();
    }
}
