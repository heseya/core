<?php

namespace App\Services;

use App\Exceptions\MediaException;
use App\Http\Requests\MediaStoreRequest;
use App\Http\Resources\MediaResource;
use App\Models\Media;
use App\Models\Product;
use App\Services\Contracts\MediaServiceContract;
use App\Services\Contracts\ReorderServiceContract;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Http;

class MediaService implements MediaServiceContract
{
    protected ReorderServiceContract $reorderService;

    public function __construct(ReorderServiceContract $reorderService)
    {
        $this->reorderService = $reorderService;
    }

    public function sync(Product $product, array $media = []): void
    {
        $product->media()->sync($this->reorderService->reorder($media));
    }

    public function store(MediaStoreRequest $request): JsonResource
    {
        $response = Http::attach('file', $request->file('file')
            ->getContent(), 'file')
            ->withHeaders(['Authorization' => config('silverbox.key')])
            ->post(config('silverbox.host') . '/' . config('silverbox.client'));

        if ($response->failed()) {
            throw new MediaException('CDN responded with an error');
        }

        $media = Media::create([
            'type' => Media::PHOTO,
            'url' => config('silverbox.host') . '/' . $response[0]['path'],
        ]);

        return MediaResource::make($media);
    }
}
