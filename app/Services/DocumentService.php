<?php

namespace App\Services;

use App\Models\Media;
use App\Models\Order;
use App\Models\OrderDocument;
use App\Services\Contracts\DocumentServiceContract;
use App\Services\Contracts\MediaServiceContract;

class DocumentService implements DocumentServiceContract
{
    public function __construct(private MediaServiceContract $mediaService)
    {
    }

    public function storeDocument(Order $order, array $data): OrderDocument
    {
        $media = $this->mediaService->store($data['file']);
        $order->documents()->attach($media, ['type' => $data['type'], 'name' => $data['name'] ?? null]);

        return OrderDocument::where([
            ['type', $data['type']],
            ['name', $data['name']],
            ['media_id', $media->getKey()],
            ['order_id', $order->getKey()],
        ])->first();
    }

    public function removeDocument(Order $order, string $mediaId): OrderDocument
    {
        $document = OrderDocument::where([
            ['media_id', $mediaId],
            ['order_id', $order->getKey()],
        ])->first();

        $this->mediaService->destroy(Media::find($document->media_id));

        return $document;
    }
}
