<?php

namespace App\Events;

use Illuminate\Support\Str;

abstract class WebHookDeletedEvent extends WebHookEvent
{
    protected array $data;

    public function __construct(array $data, string $data_type)
    {
        parent::__construct();
        $this->data = [
            'data' => $data,
            'data_type' => Str::remove('App\\Models\\', $data_type),
            'event' => Str::remove('App\\Events\\', $this::class),
            'triggered_at' => $this->triggered_at,
        ];
    }

    public function getData(): array
    {
        return $this->data;
    }
}
