<?php

namespace App\Services\Contracts;

use App\Dtos\MediaDto;
use App\Models\Media;
use App\Models\Product;

interface MediaServiceContract
{
    public function sync(Product $product, array $media): void;

    public function store(MediaDto $dto, bool $private = false): Media;

    public function update(Media $media, MediaDto $dto): Media;

    public function destroy(Media $media): void;
}
