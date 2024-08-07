<?php

namespace App\Events;

use App\Http\Resources\OrderDocumentResource;
use App\Http\Resources\OrderResource;
use App\Models\Order;
use App\Models\OrderDocument;
use Illuminate\Support\Str;

class OrderDocumentEvent extends WebHookEvent
{
    protected Order $order;
    protected OrderDocument $document;

    public function __construct(Order $order, OrderDocument $document)
    {
        parent::__construct();
        $this->order = $order;
        $this->document = $document;
    }

    public function getDataContent(): array
    {
        return ['order' => OrderResource::make($this->order)->resolve()]
            + ['documents' => OrderDocumentResource::make($this->document)->resolve()];
    }

    public function getDataType(): string
    {
        return Str::remove('App\\Events\\', $this::class);
    }
}
