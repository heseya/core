<?php

namespace App\Http\Requests;

use App\Enums\MediaType;
use BenSampo\Enum\Rules\Enum;
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
                'required_without:url',
                'prohibits:url',
                'file',
                "mimetypes:{$images}{$videos}{$documents}",
            ],
            'url' => [
                'required_without:file',
                'prohibits:file',
                'string',
            ],
            'slug' => ['string', 'max:64', 'unique:media'],
            'type' => [
                'required_with:url',
                'string',
                new Enum(MediaType::class),
            ],
        ];
    }
}
