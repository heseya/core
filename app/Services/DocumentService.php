<?php

namespace App\Services;

use App\Models\Media;
use App\Models\Order;
use App\Services\Contracts\DocumentServiceContract;
use App\Services\Contracts\MediaServiceContract;

class DocumentService implements DocumentServiceContract
{
    public function __construct(private MediaServiceContract $mediaService)
    {
    }

    public function storeDocument(Order $order, array $data): Order
    {
        $media = $this->mediaService->store($data['file']);
        $order->documents()->attach($media, ['type' => $data['type'], 'name' => $data['name'] ?? null]);

        return $order;
    }

    public function removeDocument(string $mediaId): void
    {
        $this->mediaService->destroy(Media::find($mediaId));
    }
}
