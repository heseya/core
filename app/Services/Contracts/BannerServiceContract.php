<?php

namespace App\Services\Contracts;

use App\Dtos\BannerDto;
use App\Models\Banner;

interface BannerServiceContract
{
    public function create(BannerDto $dto): Banner;

    public function update(Banner $banner, BannerDto $dto): Banner;
}
