<?php

namespace App\Events;

use App\Models\Item;
use Illuminate\Support\Str;

abstract class ItemEvent extends WebHookEvent
{
    protected Item $item;

    public function __construct(Item $item)
    {
        parent::__construct();
        $this->item = $item;
    }

    public function getData(): array
    {
        return [
            'data' => $this->item->toArray(),
            'data_type' => Str::remove('App\\Models\\', $this->item::class),
            'event' => Str::remove('App\\Events\\', $this::class),
            'triggered_at' => $this->triggered_at,
        ];
    }
}
