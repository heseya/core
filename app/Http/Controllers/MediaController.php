<?php

namespace App\Http\Controllers;

use App\Exceptions\MediaException;
use App\Http\Controllers\Swagger\MediaControllerSwagger;
use App\Http\Requests\MediaStoreRequest;
use App\Http\Resources\MediaResource;
use App\Models\Media;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Response as HttpRespone;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Response;

class MediaController extends Controller implements MediaControllerSwagger
{
    public function store(MediaStoreRequest $request): JsonResource
    {
        $response = Http::attach('file', $request->file('file')->getContent(), 'file')
            ->withHeaders(['Authorization' => config('silverbox.key')])
            ->post(config('silverbox.host') . '/' . config('silverbox.client'));

        if ($response->failed()) {
            throw new MediaException('CDN responded with an error');
        }

        $media = Media::create([
            'type' => Media::PHOTO,
            'url' => config('silverbox.host') . '/' . $response[0]['path'],
        ]);

        return MediaResource::make($media);
    }

    public function destroyByImage(Media $media): JsonResponse
    {
        if ($media->type !== Media::PHOTO) {
            throw new MediaException('Media image not found');
        }

        $media->forceDelete();

        return Response::json(null, HttpRespone::HTTP_NO_CONTENT);
    }
}
