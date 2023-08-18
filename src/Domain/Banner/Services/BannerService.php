<?php

declare(strict_types=1);

namespace Domain\Banner\Services;

use App\Services\Contracts\MetadataServiceContract;
use Domain\Banner\Dtos\BannerCreateDto;
use Domain\Banner\Dtos\BannerMediaCreateDto;
use Domain\Banner\Dtos\BannerUpdateDto;
use Domain\Banner\Models\Banner;
use Domain\Banner\Models\BannerMedia;
use Spatie\LaravelData\Optional;

final class BannerService
{
    public function __construct(private MetadataServiceContract $metadataService) {}

    public function create(BannerCreateDto $dto): Banner
    {
        $banner = Banner::create($dto->toArray());

        if (!$dto->banner_media instanceof Optional) {
            /**
             * @var int $index
             * @var BannerMediaCreateDto $group
             */
            foreach ($dto->banner_media as $index => $group) {
                /** @var BannerMedia $bannerMedia */
                $bannerMedia = $banner->bannerMedia()->make([
                    'url' => $group->url,
                    'order' => $index + 1,
                ]);
                foreach ($group->translations as $lang => $translation) {
                    $bannerMedia->setLocale($lang)->fill($translation);
                }
                $bannerMedia->fill(['published' => $group->published]);
                $bannerMedia->save();

                foreach ($group->media as $media) {
                    $bannerMedia->media()->attach($media->media, [
                        'min_screen_width' => $media->min_screen_width,
                    ]);
                }
            }
        }

        if (!($dto->metadata instanceof Optional)) {
            $this->metadataService->sync($banner, $dto->metadata);
        }

        return $banner;
    }

    public function update(Banner $banner, BannerUpdateDto $dto): Banner
    {
        $banner->update($dto->toArray());
        $banner->bannerMedia()->delete();

        if (!$dto->banner_media instanceof Optional) {
            foreach ($dto->banner_media as $index => $group) {
                $bannerMedia = null;
                if (!$group->id instanceof Optional) {
                    /** @var BannerMedia $bannerMedia */
                    $bannerMedia = $banner->BannerMedia()->firstWhere('id', '=', $group->id);
                    $bannerMedia->update($group->toArray() + ['order' => $index + 1]);
                }

                if (!$bannerMedia) {
                    /** @var BannerMedia $bannerMedia */
                    $bannerMedia = $banner->BannerMedia()->make($group->toArray() + [
                        'url' => $group->url,
                        'order' => $index + 1,
                    ]);
                }

                if (!$group->translations instanceof Optional) {
                    foreach ($group->translations as $lang => $translation) {
                        $bannerMedia->setLocale($lang)->fill($translation);
                    }
                }
                $bannerMedia->save();

                $medias = [];
                if (!$group->media instanceof Optional) {
                    foreach ($group->media as $media) {
                        $medias[$media->media] = ['min_screen_width' => $media->min_screen_width];
                    }
                }
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
