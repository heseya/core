<?php

namespace App\Services\Contracts;

use App\Dtos\MediaUpdateDto;
use App\Models\Media;
use App\Models\Product;
use Illuminate\Http\UploadedFile;

interface MediaServiceContract
{
    public function sync(Product $product, array $media): void;

    public function store(UploadedFile $file, bool $private = false): Media;

    public function update(Media $media, MediaUpdateDto $dto): Media;

    public function destroy(Media $media): void;
}
