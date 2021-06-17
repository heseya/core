<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Swagger\MediaControllerSwagger;
use App\Http\Requests\MediaStoreRequest;
use App\Http\Resources\MediaResource;
use App\Models\Media;
use Exception;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Http;

class MediaController extends Controller implements MediaControllerSwagger
{
    public function store(MediaStoreRequest $request): JsonResource
    {
        $response = Http::attach(
                'file',
                file_get_contents($request->file('file')),
                'file',
            )
            ->withHeaders(['Authorization' => config('silverbox.key')])
            ->post(config('silverbox.host') . '/' . config('silverbox.client'));

        if ($response->failed()) {
            throw new Exception('CDN responded with an error');
        }

        $media = Media::create([
            'type' => Media::PHOTO,
            'url' => config('silverbox.host') . '/' . $response[0]['path'],
        ]);

        return MediaResource::make($media);
    }
}
