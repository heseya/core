<?php

namespace App\Http\Controllers\Admin;

use Unirest;
use App\Photo;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class MediaController extends Controller
{
    public function uploadPhoto(Request $request)
    {
        $body = Unirest\Request\Body::multipart([], ['photo' => $request->photo]);
        $response = Unirest\Request::post(config('cdn.host'), config('cdn.headers'), $body);

        $photo = Photo::create([
            'url' => config('cdn.host') . '/' . $response->body[0]->id,
        ]);

        return $photo->id;
    }
}
