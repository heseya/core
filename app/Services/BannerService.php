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

        foreach ($dto->getResponsiveMedia()->all() as $index => $media) {
            $responsiveMedia = $banner->responsiveMedia()->create([
                'order' => $index + 1,
            ]);

            $responsiveMedia->media()->attach($media->getMedia(), [
                'min_screen_width' => $media->getMinScreenWidth(),
            ]);
        }

        return $banner;
    }
}
