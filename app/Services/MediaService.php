<?php

namespace App\Services;

use App\Models\Product;
use App\Services\Contracts\MediaServiceContract;

class MediaService implements MediaServiceContract
{
    public function sync(Product $product, array $media = []): void
    {
        $product->media()->sync($this->reorder($media));
    }

    private function reorder(array $media): array
    {
        $array = [];

        foreach ($media as $key => $id) {
            $array[$id]['order'] = $key;
        }

        return $array;
    }
}
