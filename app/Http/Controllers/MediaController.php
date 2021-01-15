<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Swagger\MediaControllerSwagger;
use App\Http\Resources\MediaResource;
use App\Models\Media;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class MediaController extends Controller implements MediaControllerSwagger
{
    public function store(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:jpeg,png,gif,bmp',
        ]);

        $response = Http::attach('file', file_get_contents($request->file('file')), 'file')
            ->withHeaders([
                'Authorization' => config('silverbox.key'),
            ])
            ->post(config('silverbox.host') . '/' . config('silverbox.client'));

        if ($response->failed()) {
            $response = config('app.debug') ? [
                'error' => true,
                'silverbox-code' => $response->status(),
                'silverbox-response' => $response->body(),
            ] : ['error' => true];

            return response()->json($response, 500);
        }

        $media = Media::create([
            'type' => Media::PHOTO,
            'url' => rtrim(config('silverbox.host') . '/') . '/' . $response[0]->path,
        ]);

        return MediaResource::make($media);
    }
}
