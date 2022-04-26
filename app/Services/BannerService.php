<?php

namespace App\Services;

use App\Dtos\BannerDto;
use App\Models\Banner;
use App\Services\Contracts\BannerServiceContract;
use Illuminate\Support\Collection;

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
                $media->each(function ($responsiveMedia) use ($bannerMedia): void {
                    $bannerMedia->media()->attach($responsiveMedia->getMedia(), [
                        'min_screen_width' => $responsiveMedia->getMinScreenWidth(),
                    ]);
                });
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

            $medias = new Collection([]);

            $group->getMedia()->each(function ($media) use (&$medias): void {
                $medias->merge($media->mapWithKeys(fn ($media) => [
                    $media->getMedia() => ['min_screen_width' => $media->getMinScreenWidth()],
                ]));
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
