<?php

namespace App\Events;

use App\Http\Resources\OrderDocumentResource;
use Illuminate\Support\Collection;

class SendOrderDocument extends WebHookEvent
{
    protected Collection $documents;

    public function __construct(Collection $documents)
    {
        parent::__construct();
        $this->documents = $documents;
    }

    public function getDataContent(): array
    {
        return OrderDocumentResource::collection($this->documents)->resolve();
    }

    public function getDataType(): string
    {
        return $this->getModelClass($this->documents->first());
    }
}
