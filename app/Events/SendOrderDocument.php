<?php

namespace App\Events;

use App\Models\Order;
use Domain\Order\Resources\OrderDocumentResource;
use Domain\Order\Resources\OrderResource;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class SendOrderDocument extends WebHookEvent
{
    protected Order $order;
    protected Collection $documents;

    public function __construct(Order $order, Collection $documents)
    {
        parent::__construct();

        $this->order = $order;
        $this->documents = $documents;
    }

    public function getDataContent(): array
    {
        return ['order' => OrderResource::make($this->order)->resolve()]
            + ['documents' => OrderDocumentResource::collection($this->documents)->resolve()];
    }

    public function getDataType(): string
    {
        return Str::remove('App\\Events\\', $this::class);
    }
}
