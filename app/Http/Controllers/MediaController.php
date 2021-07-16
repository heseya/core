<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Swagger\MediaControllerSwagger;
use App\Http\Requests\MediaStoreRequest;
use App\Models\Media;
use App\Services\Contracts\MediaServiceContract;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\JsonResource;

class MediaController extends Controller implements MediaControllerSwagger
{
    private MediaServiceContract $mediaServiceContract;

    public function __construct(MediaServiceContract $mediaServiceContract)
    {
        $this->mediaServiceContract = $mediaServiceContract;
    }

    public function store(MediaStoreRequest $request): JsonResource
    {
        return $this->mediaServiceContract->store($request);
    }

    public function destroyByImage(Media $media): JsonResponse
    {
        return $this->mediaServiceContract->destroyByImage($media);
    }
}
