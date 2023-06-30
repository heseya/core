<?php

namespace App\Http\Requests;

use App\Enums\MediaSource;
use App\Enums\MediaType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class MediaStoreRequest extends FormRequest
{
    public function rules(): array
    {
        $images = 'image/jpeg,image/png,image/gif,image/bmp,image/svg+xml,image/webp,';
        $videos = 'video/mp4,video/webm,video/ogg,video/quicktime,video/x-ms-wmv,video/x-ms-asf,';
        $documents = 'application/pdf';

        return [
            'id' => ['nullable', 'uuid'],

            'alt' => ['nullable', 'string', 'max:100'],
            'file' => [
                'required_without:url',
                'prohibits:url',
                'file',
                "mimetypes:{$images}{$videos}{$documents}",
            ],
            'url' => [
                'required_without:file',
                'prohibits:file',
                'string',
                'max:500',
            ],
            'slug' => ['string', 'max:64', 'unique:media'],
            'type' => [
                'required_with:url',
                new Enum(MediaType::class),
            ],
            'source' => [
                new Enum(MediaSource::class),
            ],
        ];
    }
}
