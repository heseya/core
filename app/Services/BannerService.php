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

        foreach ($dto->getResponsiveMedia()->all() as $index => $group) {
            $responsiveMedia = $banner->responsiveMedia()->create([
                'order' => $index + 1,
            ]);

            $group->each(function ($media) use ($responsiveMedia): void {
                $responsiveMedia->media()->attach($media->getMedia(), [
                    'min_screen_width' => $media->getMinScreenWidth(),
                ]);
            });
        }

        return $banner;
    }

    public function update(Banner $banner, BannerDto $dto): Banner
    {
        $banner->update($dto->toArray());

        foreach ($dto->getResponsiveMedia()->all() as $index => $group) {
            $responsiveMedia = $banner->responsiveMedia()->firstOrCreate([
                'order' => $index + 1,
            ]);

            $medias = $group->mapWithKeys(fn ($media) => [
                $media->getMedia() => ['min_screen_width' => $media->getMinScreenWidth()],
            ]);

            $responsiveMedia->media()->sync($medias);
        }

        if ($dto->getResponsiveMedia()->count() < $banner->responsiveMedia()->count()) {
            $banner
                ->responsiveMedia()
                ->where('order', '>', $dto->getResponsiveMedia()->count())
                ->delete();
        }

        return $banner->refresh();
    }

    public function delete(Banner $banner): bool
    {
        return $banner->delete();
    }
}
