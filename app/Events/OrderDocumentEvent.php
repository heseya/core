<?php

namespace App\Events;

use App\Http\Resources\OrderDocumentResource;
use App\Models\OrderDocument;

class OrderDocumentEvent extends WebHookEvent
{
    protected OrderDocument $document;

    public function __construct(OrderDocument $document)
    {
        parent::__construct();
        $this->document = $document;
    }

    public function getDataContent(): array
    {
        return OrderDocumentResource::make($this->document->pivot)->resolve();
    }

    public function getDataType(): string
    {
        return $this->getModelClass($this->document);
    }
}
