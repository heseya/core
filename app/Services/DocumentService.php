<?php

namespace App\Services;

use App\Models\Media;
use App\Models\Order;
use App\Models\OrderDocument;
use App\Services\Contracts\DocumentServiceContract;
use App\Services\Contracts\MediaServiceContract;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class DocumentService implements DocumentServiceContract
{
    public function __construct(private MediaServiceContract $mediaService)
    {
    }

    public function storeDocument(Order $order, ?string $name, string $type, UploadedFile $file): OrderDocument
    {
        $media = $this->mediaService->store($file, true);
        $order->documents()->attach($media, ['type' => $type, 'name' => $name ?? null]);

        return OrderDocument::where([
            ['type', $type],
            ['name', $name],
            ['media_id', $media->getKey()],
            ['order_id', $order->getKey()],
        ])->first();
    }

    public function downloadDocument(OrderDocument $document)
    {
        return response()->streamDownload(function () use ($document): void {
            echo Http::withHeaders(['x-api-key' => Config::get('silverbox.key')])
                ->get($document->media->url);
        }, Str::of($document->media->url)->afterLast('/'));
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
