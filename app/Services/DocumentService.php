<?php

namespace App\Services;

use App\Dtos\MediaDto;
use App\Models\Media;
use App\Models\Order;
use App\Models\OrderDocument;
use App\Services\Contracts\DocumentServiceContract;
use App\Services\Contracts\MediaServiceContract;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DocumentService implements DocumentServiceContract
{
    public function __construct(
        private MediaServiceContract $mediaService,
    ) {
    }

    public function storeDocument(Order $order, ?string $name, string $type, UploadedFile $file): OrderDocument
    {
        $mediaDto = MediaDto::instantiateFromFile($file);

        $media = $this->mediaService->store($mediaDto, true);
        $order->documents()->attach($media, ['type' => $type, 'name' => $name ?? null]);

        return OrderDocument::query()->where([
            ['type', $type],
            ['name', $name],
            ['media_id', $media->getKey()],
            ['order_id', $order->getKey()],
        ])->firstOrFail();
    }

    public function downloadDocument(OrderDocument $document): StreamedResponse
    {
        /** @var string $url */
        $url = $document->media?->url;

        $mime = Http::withHeaders(['x-api-key' => Config::get('silverbox.key')])
            ->get($url . '/info')
            ->json('mime');

        return response()->streamDownload(function () use ($url): void {
            echo Http::withHeaders(['x-api-key' => Config::get('silverbox.key')])
                ->get($url);
        }, Str::of($url)->afterLast('/'), [
            'Content-Type' => $mime,
        ]);
    }

    public function removeDocument(Order $order, string $mediaId): OrderDocument
    {
        $document = OrderDocument::query()->where([
            ['media_id', $mediaId],
            ['order_id', $order->getKey()],
        ])->firstOrFail();

        $this->mediaService->destroy(Media::where('id', $document->media_id)->firstOrFail());

        return $document;
    }
}
