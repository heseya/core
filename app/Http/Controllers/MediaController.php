<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Swagger\MediaControllerSwagger;
use App\Http\Requests\MediaStoreRequest;
use App\Http\Resources\MediaResource;
use App\Models\Media;
use App\Services\Contracts\MediaServiceContract;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Response;

class MediaController extends Controller implements MediaControllerSwagger
{
    private MediaServiceContract $mediaServiceContract;

    public function __construct(MediaServiceContract $mediaServiceContract)
    {
        $this->mediaServiceContract = $mediaServiceContract;
    }

    public function store(MediaStoreRequest $request): JsonResource
    {
        $media = $this->mediaServiceContract->store($request->file('file'));

        return MediaResource::make($media);
    }

    public function destroy(Media $media): JsonResponse
    {
        $this->mediaServiceContract->destroy($media);

        return Response::json(null, JsonResponse::HTTP_NO_CONTENT);
    }
}
