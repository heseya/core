<?php

namespace App\Services;

use App\Dtos\BannerDto;
use App\Models\Banner;
use App\Services\Contracts\BannerServiceContract;
use App\Services\Contracts\MetadataServiceContract;
use Heseya\Dto\Missing;

class BannerService implements BannerServiceContract
{
    public function __construct(private MetadataServiceContract $metadataService) {}

    public function create(BannerDto $dto): Banner
    {
        $banner = Banner::create($dto->toArray());

        if (!$dto->getBannerMedia() instanceof Missing) {
            foreach ($dto->getBannerMedia()->all() as $index => $group) {
                $bannerMedia = $banner->bannerMedia()->create([
                    'title' => $group->getTitle(),
                    'subtitle' => $group->getSubtitle(),
                    'url' => $group->getUrl(),
                    'order' => $index + 1,
                ]);

                $group->getMedia()->each(function ($media) use ($bannerMedia): void {
                    $bannerMedia->media()->attach($media->getMedia(), [
                        'min_screen_width' => $media->getMinScreenWidth(),
                    ]);
                });
            }
        }

        if (!($dto->getMetadata() instanceof Missing)) {
            $this->metadataService->sync($banner, $dto->getMetadata());
        }

        return $banner;
    }

    public function update(Banner $banner, BannerDto $dto): Banner
    {
        $banner->update($dto->toArray());
        $banner->bannerMedia()->delete();

        if (!$dto->getBannerMedia() instanceof Missing) {
            foreach ($dto->getBannerMedia()->all() as $index => $group) {
                $bannerMedia = $banner->BannerMedia()->firstOrCreate([
                    'title' => $group->getTitle(),
                    'subtitle' => $group->getSubtitle(),
                    'url' => $group->getUrl(),
                    'order' => $index + 1,
                ]);

                $medias = [];
                $group->getMedia()->each(function ($media) use (&$medias): void {
                    $medias[$media->getMedia()] = ['min_screen_width' => $media->getMinScreenWidth()];
                });
                $bannerMedia->media()->sync($medias);
            }
        }

        return $banner->refresh();
    }

    public function delete(Banner $banner): bool
    {
        return (bool) $banner->delete();
    }
}
