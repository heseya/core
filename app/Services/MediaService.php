<?php

namespace App\Services;

use App\Dtos\MediaUpdateDto;
use App\Enums\MediaType;
use App\Exceptions\AppAccessException;
use App\Exceptions\MediaCriticalException;
use App\Models\Media;
use App\Models\Product;
use App\Services\Contracts\MediaServiceContract;
use App\Services\Contracts\ReorderServiceContract;
use Heseya\Dto\Missing;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Config;
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
        $operations = $product->media()->sync($this->reorderService->reorder($media));

        if (array_key_exists('detached', $operations) && $operations['detached']) {
            Media::whereIn('id', $operations['detached'])
                ->each(function ($object): void {
                    $this->destroy($object);
                });
        }
    }

    public function store(UploadedFile $file): Media
    {
        $response = Http::attach('file', $file->getContent(), 'file')
            ->withHeaders(['x-api-key' => Config::get('silverbox.key')])
            ->post(Config::get('silverbox.host') . '/' . Config::get('silverbox.client'));

        if ($response->failed()) {
            throw new MediaCriticalException('CDN responded with an error');
        }

        return Media::create([
            'type' => $this->getMediaType($file->extension()),
            'url' => Config::get('silverbox.host') . '/' . $response[0]['path'],
        ]);
    }

    public function update(Media $media, MediaUpdateDto $dto): Media
    {
        if (!($dto->getSlug() instanceof Missing) && $media->slug !== $dto->getSlug()) {
            $media->url = $this->updateSlug($media, $dto->getSlug());
            $media->slug = $dto->getSlug();
        }

        if (!($dto->getAlt() instanceof Missing)) {
            $media->alt = $dto->getAlt();
        }

        $media->save();

        return $media;
    }

    public function destroy(Media $media): void
    {
        if ($media->products()->exists()) {
            Gate::authorize('products.edit');
        }

        $response = Http::withHeaders(['x-api-key' => Config::get('silverbox.key')])
            ->delete($media->url);

        if ($response->failed()) {
            throw new MediaCriticalException('CDN responded with an error');
        }

        $media->forceDelete();
    }

    private function getMediaType(string $extension): int
    {
        return match ($extension) {
            'jpeg', 'jpg', 'png', 'gif', 'bmp', 'svg', 'webp' => MediaType::PHOTO,
            'mp4', 'webm', 'ogg', 'ogv', 'mov', 'wmv' => MediaType::VIDEO,
            default => MediaType::OTHER,
        };
    }

    private function updateSlug(Media $media, string $slug): string
    {
        $response = Http::asJson()
            ->acceptJson()
            ->withHeaders(['x-api-key' => Config::get('silverbox.key')])
            ->patch($media->url, [
                'slug' => $slug,
            ]);

        if ($response->failed() || !isset($response['path'])) {
            throw new AppAccessException('CDN responded with an error', 500);
        }

        return Config::get('silverbox.host') . '/' . $response['path'];
    }
}
