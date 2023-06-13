<?php

namespace App\Services;

use App\Dtos\MediaDto;
use App\Enums\MediaSource;
use App\Enums\MediaType;
use App\Models\Media;
use App\Models\Product;
use App\Services\Contracts\MediaServiceContract;
use App\Services\Contracts\MetadataServiceContract;
use App\Services\Contracts\ReorderServiceContract;
use App\Services\Contracts\SilverboxServiceContract;
use Heseya\Dto\Missing;
use Illuminate\Http\UploadedFile;

final readonly class MediaService implements MediaServiceContract
{
    public function __construct(
        private ReorderServiceContract $reorderService,
        private MetadataServiceContract $metadataService,
        private SilverboxServiceContract $silverboxService,
    ) {
    }

    public function sync(Product $product, array $media): void
    {
        $operations = $product->media()->sync($this->reorderService->reorder($media));

        if (array_key_exists('detached', $operations) && $operations['detached']) {
            Media::query()->whereIn('id', $operations['detached'])
                ->each(function ($object): void {
                    $this->destroy($object);
                });
        }
    }

    public function destroy(Media $media): void
    {
        $this->silverboxService->delete($media);
        $media->forceDelete();
    }

    public function store(MediaDto $dto, bool $private = false): Media
    {
        /** @var UploadedFile $file */
        $file = $dto->file;

        [$url, $type] = match ($dto->source) {
            MediaSource::EXTERNAL => [$dto->url, $dto->type],
            MediaSource::SILVERBOX => [
                $this->silverboxService->upload($file, $private),
                $this->getMediaType($file->extension()),
            ],
        };

        if (!($dto->slug instanceof Missing)) {
            // @phpstan-ignore-next-line
            $url = $this->silverboxService->updateSlug($url, $dto->slug);
        }

        $data = [
            'type' => $type,
            'url' => $url,
            'alt' => $dto->alt instanceof Missing ? null : $dto->alt,
            'slug' => $dto->slug instanceof Missing ? null : $dto->slug,
            'source' => $dto->source,
        ];

        if (!($dto->id instanceof Missing)) {
            $data['id'] = $dto->id;
        }

        /** @var Media $media */
        $media = Media::query()->create($data);

        if (!($dto->getMetadata() instanceof Missing)) {
            $this->metadataService->sync($media, $dto->getMetadata());
        }

        return $media;
    }

    public function update(Media $media, MediaDto $dto): Media
    {
        if (!($dto->slug instanceof Missing)) {
            if ($media->slug !== $dto->slug && $dto->slug !== null) {
                $media->url = $this->silverboxService->updateSlug($media->url, $dto->slug);
            }

            $media->slug = $dto->slug;
        }

        if (!($dto->alt instanceof Missing)) {
            $media->alt = $dto->alt;
        }

        $media->save();

        return $media;
    }

    private function getMediaType(string $extension): MediaType
    {
        return match ($extension) {
            'jpeg', 'jpg', 'png', 'gif', 'bmp', 'svg', 'webp' => MediaType::PHOTO,
            'mp4', 'webm', 'ogg', 'ogv', 'mov', 'wmv' => MediaType::VIDEO,
            'pdf', 'doc', 'docx', 'odt', 'xls', 'xlsx' => MediaType::DOCUMENT,
            default => MediaType::OTHER,
        };
    }
}
