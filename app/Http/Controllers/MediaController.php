<?php

namespace App\Http\Controllers;

use App\Media;
use Heseya\Silverbox;
use Illuminate\Http\Request;
use App\Http\Resources\MediaResource;
use Illuminate\Http\Resources\Json\ResourceCollection;

class MediaController extends Controller
{
    /**
     * @OA\Post(
     *   path="/media",
     *   summary="upload new file",
     *   tags={"Media"},
     *   @OA\RequestBody(
     *     @OA\MediaType(
     *       mediaType="multipart/form-data",
     *       @OA\Schema(
     *         @OA\Property(
     *           property="file",
     *           description="File.",
     *           type="binary",
     *         ),
     *       ),
     *     ),
     *   ),
     *   @OA\Response(
     *     response=200,
     *     description="Success",
     *     @OA\JsonContent(
     *       @OA\Property(
     *         property="data",
     *         type="array",
     *         @OA\Items(ref="#/components/schemas/Media"),
     *       )
     *     )
     *   )
     * )
     */
    public function upload(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:jpeg,bmp,png',
        ]);

        $silverbox = new Silverbox(config('silverbox.host'));
        $response = $silverbox
            ->as(config('silverbox.client'), config('silverbox.key'))
            ->upload($request->file('file'));

        $media = Media::create([
            'type' => Media::PHOTO,
            'url' => rtrim(config('silverbox.host'). '/') . '/' . $response[0]->path,
        ]);

        return new MediaResource($media);
    }
}
