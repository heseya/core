<?php

namespace App\Services\Contracts;

use Illuminate\Http\UploadedFile;

interface SilverboxServiceContract
{
    public function upload(UploadedFile $file, bool $private): string;

    public function updateSlug(string $url, ?string $slug): string;

    public function delete(string $url): void;
}
