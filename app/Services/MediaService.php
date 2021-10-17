<?php

namespace App\Services;

use App\Enums\MediaType;
use App\Exceptions\MediaException;
use App\Models\Media;
use App\Models\Product;
use App\Services\Contracts\MediaServiceContract;
use App\Services\Contracts\ReorderServiceContract;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Gate;
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

    public function store(UploadedFile $file): Media
    {
        $response = Http::attach('file', $file->getContent(), 'file')
            ->withHeaders(['x-api-key' => config('silverbox.key')])
            ->post(config('silverbox.host') . '/' . config('silverbox.client'));

        if ($response->failed()) {
            throw new MediaException('CDN responded with an error');
        }

        return Media::create([
            'type' => $this->getMediaType($file->extension()),
            'url' => config('silverbox.host') . '/' . $response[0]['path'],
        ]);
    }

    public function destroy(Media $media): void
    {
        if ($media->products()->exists()) {
            Gate::authorize('products.edit');
        }

        $media->forceDelete();
    }

    private function getMediaType(string $extension): int
    {
        return match ($extension) {
            'jpeg', 'jpg', 'png', 'gif', 'bmp', 'svg' => MediaType::PHOTO,
            'mp4', 'webm' => MediaType::VIDEO,
            default => MediaType::OTHER,
        };
    }
}
