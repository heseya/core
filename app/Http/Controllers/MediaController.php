<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Swagger\MediaControllerSwagger;
use App\Http\Resources\MediaResource;
use App\Models\Media;
use Heseya\Silverbox;
use Illuminate\Http\Request;

class MediaController extends Controller implements MediaControllerSwagger
{
    public function store(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:jpeg,png,gif,bmp',
        ]);

        $silverbox = new Silverbox(config('silverbox.host'));
        $response = $silverbox
            ->as(config('silverbox.client'), config('silverbox.key'))
            ->upload($request->file('file'));

        $media = Media::create([
            'type' => Media::PHOTO,
            'url' => rtrim(config('silverbox.host'). '/') . '/' . $response[0]->path,
        ]);

        return MediaResource::make($media)
            ->response()
            ->setStatusCode(201);
    }
}
