<?php

namespace App\Events;

use App\Http\Resources\ItemResource;
use App\Models\Item;

abstract class ItemEvent extends WebHookEvent
{
    protected Item $item;

    public function __construct(Item $item)
    {
        parent::__construct();
        $this->item = $item;
    }

    public function getDataContent(): array
    {
        return ItemResource::make($this->item)->resolve();
    }

    public function getDataType(): string
    {
        return $this->getModelClass($this->item);
    }
}
