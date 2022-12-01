<?php

namespace App\Services;

use App\Dtos\MediaDto;
use App\Enums\ExceptionsEnums\Exceptions;
use App\Enums\MediaType;
use App\Exceptions\ClientException;
use App\Exceptions\ServerException;
use App\Models\Media;
use App\Models\Product;
use App\Services\Contracts\MediaServiceContract;
use App\Services\Contracts\MetadataServiceContract;
use App\Services\Contracts\ReorderServiceContract;
use Heseya\Dto\Missing;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class MediaService implements MediaServiceContract
{
    public function __construct(
        private ReorderServiceContract $reorderService,
        private MetadataServiceContract $metadataService,
    ) {
    }

    public function sync(Product $product, array $media): void
    {
        $operations = $product->media()->sync($this->reorderService->reorder($media));

        if (array_key_exists('detached', $operations) && $operations['detached']) {
            Media::whereIn('id', $operations['detached'])
                ->each(function ($object): void {
                    $this->destroy($object);
                });
        }
    }

    public function destroy(Media $media): void
    {
        if (Str::contains($media->url, Config::get('silverbox.host'))) {
            Http::withHeaders(['x-api-key' => Config::get('silverbox.key')])
                ->delete($media->url);
        }

        // no need to handle failed response while removing media

        $media->forceDelete();
    }

    /**
     * @throws ClientException
     * @throws ServerException
     */
    public function store(MediaDto $dto, bool $private = false): Media
    {
        if ($dto->getFile() instanceof Missing) {
            // TODO jakiś komunikat dodać
            throw new ClientException();
        }
        $private = $private ? '?private' : '';

        $response = Http::attach('file', $dto->getFile()->getContent(), 'file')
            ->withHeaders(['x-api-key' => Config::get('silverbox.key')])
            ->post(Config::get('silverbox.host') . '/' . Config::get('silverbox.client') . $private);

        if ($response->failed()) {
            throw new ServerException(
                message: Exceptions::SERVER_CDN_ERROR,
                errorArray: $response->json(),
            );
        }

        $url = Config::get('silverbox.host') . '/' . $response->json('0.path');
        if (!$dto->getSlug() instanceof Missing) {
            $url = $this->updateSlug($url, $dto->getSlug());
        }

        $media = Media::create([
            'type' => $this->getMediaType($dto->getFile()->extension()),
            'url' => $url,
            'alt' => $dto->getAlt() instanceof Missing ? null : $dto->getAlt(),
            'slug' => $dto->getSlug() instanceof Missing ? null : $dto->getSlug(),
        ]);

        if (!($dto->getMetadata() instanceof Missing)) {
            $this->metadataService->sync($media, $dto->getMetadata());
        }

        return $media;
    }

    public function update(Media $media, MediaDto $dto): Media
    {
        if (!($dto->getSlug() instanceof Missing)) {
            if ($media->slug !== $dto->getSlug() && $dto->getSlug() !== null) {
                $media->url = $this->updateSlug($media->url, $dto->getSlug());
            }

            $media->slug = $dto->getSlug();
        }

        if (!($dto->getAlt() instanceof Missing)) {
            $media->alt = $dto->getAlt();
        }

        $media->save();

        return $media;
    }

    private function getMediaType(string $extension): string
    {
        return match ($extension) {
            'jpeg', 'jpg', 'png', 'gif', 'bmp', 'svg', 'webp' => MediaType::PHOTO,
            'mp4', 'webm', 'ogg', 'ogv', 'mov', 'wmv' => MediaType::VIDEO,
            'pdf', 'doc', 'docx', 'odt', 'xls', 'xlsx' => MediaType::DOCUMENT,
            default => MediaType::OTHER,
        };
    }

    private function updateSlug(string $mediaUrl, ?string $slug): string
    {
        if (!Str::contains($mediaUrl, Config::get('silverbox.host'))) {
            throw new ClientException(message: Exceptions::CDN_NOT_ALLOWED_TO_CHANGE_ALT);
        }

        $response = Http::asJson()
            ->acceptJson()
            ->withHeaders(['x-api-key' => Config::get('silverbox.key')])
            ->patch($mediaUrl, [
                'slug' => $slug,
            ]);

        if ($response->failed() || !isset($response['path'])) {
            throw new ServerException(
                message: Exceptions::SERVER_CDN_ERROR,
                errorArray: $response->json() ?? [],
            );
        }

        return Config::get('silverbox.host') . '/' . $response['path'];
    }
}
