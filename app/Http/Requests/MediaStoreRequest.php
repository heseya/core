<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class MediaStoreRequest extends FormRequest
{
    public function rules(): array
    {
        $images = 'image/jpeg,image/png,image/gif,image/bmp,image/svg+xml,image/webp,';
        $videos = 'video/mp4,video/webm,video/ogg,video/quicktime,video/x-ms-wmv,video/x-ms-asf,';
        $documents = 'application/pdf';

        return [
            'alt' => ['nullable', 'string', 'max:100'],
            'file' => [
                'required',
                'file',
                "mimetypes:${images}${videos}${documents}",
            ],
            'slug' => ['string', 'max:64', 'unique:media'],
        ];
    }
}
