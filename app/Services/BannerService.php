<?php

namespace App\Services;

use App\Dtos\BannerDto;
use App\Models\Banner;
use App\Services\Contracts\BannerServiceContract;

class BannerService implements BannerServiceContract
{
    public function create(BannerDto $dto): Banner
    {
        $banner = Banner::create($dto->toArray());

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

        return $banner;
    }

    public function update(Banner $banner, BannerDto $dto): Banner
    {
        $banner->update($dto->toArray());

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

        if ($dto->getBannerMedia()->count() < $banner->BannerMedia()->count()) {
            $banner
                ->BannerMedia()
                ->where('order', '>', $dto->getBannerMedia()->count())
                ->delete();
        }

        return $banner->refresh();
    }

    public function delete(Banner $banner): bool
    {
        return $banner->delete();
    }
}
