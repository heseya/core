<?php

namespace App\Http\Requests;

use App\Enums\MediaSource;
use App\Enums\MediaType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rules\Enum;

class MediaStoreRequest extends FormRequest
{
    public function rules(): array
    {
        $images = 'image/jpeg,image/png,image/gif,image/bmp,image/svg+xml,image/webp,';
        $videos = 'video/mp4,video/webm,video/ogg,video/quicktime,video/x-ms-wmv,video/x-ms-asf,video/x-msvideo,';
        $documents = 'application/pdf';

        /** @var UploadedFile $file */
        $file = $this->file()['file'];
        Log::info('----------------------------------------------');
        Log::info('Media Store File name ' . $file->getFilename());
        Log::info('Media Store File client name ' . $file->getClientOriginalName());
        Log::info('Media Store File mime ' . $file->getMimeType());
        Log::info('Media Store File client mime ' . $file->getClientMimeType());
        Log::info('Media Store File size ' . $file->getSize());
        Log::info('Media Store File real path ' . $file->getRealPath());
        Log::info('Media Store File error ' . $file->getError());
        Log::info('Media Store File error message ' . $file->getErrorMessage());
        Log::info('Media Store File type ' . $file->getType());
        Log::info('----------------------------------------------');

        return [
            'id' => ['uuid'],

            'alt' => ['nullable', 'string', 'max:255'],
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
